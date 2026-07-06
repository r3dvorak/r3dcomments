<?php
/**
 * @package     com_r3dcomments
 * @version     5.1.1
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Richard Dvořák, <dev@r3d.de> - https://r3d.de
 */

namespace Joomla\Component\R3dcomments\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Crypt\Crypt;
use Joomla\CMS\Captcha\Captcha;
use Joomla\CMS\User\UserFactoryInterface;
use RuntimeException;
use DateInterval;

/**
 * R3dcomments front-end comment controller
 */
class CommentController extends FormController
{
    /**
     * The list view to use after save
     *
     * @var string
     */
    protected $view_list = 'comments';

    /**
     * Method to display the edit form.
     *
     * @param   string  $cachable   If true, the view output will be cached.
     * @param   array   $urlparams  An array of safe url parameters and their variable types.
     *
     * @return  void
     *
     * @since   5.2.7
     */
    public function edit($key = null, $urlVar = 'id')
    {
        // Hole Joomla Application
        $app = $this->app ?? Factory::getApplication();

        // Kommentar-ID aus der URL
        $id = $app->getInput()->getInt('id');

        if (!$id) {
            throw new \RuntimeException('Missing comment id', 400);
        }

        // Model holen
        /** @var \Joomla\Component\R3dcomments\Site\Model\CommentModel $model */
        $model = $this->getModel('Comment');

        // VERY IMPORTANT: State korrekt setzen
        $model->setState('comment.id', $id);

        // Sichten definieren
        $this->view_item = 'comment';
        $this->view_list = 'comments';

        // Standardanzeige durchführen
        return parent::display();
    }

    /**
     * Handle saving of a comment from the front-end form.
     *
     * This replaces the old sendInformation() logic and is called via:
     * index.php?option=com_r3dcomments&task=comment.save
     *
     * @param  string|null $key
     * @param  string      $urlVar
     *
     * @return void
     */
    public function save($key = null, $urlVar = 'id'): void
    {
        // CSRF check
        if (!Session::checkToken('post'))
        {
            throw new RuntimeException(Text::_('JINVALID_TOKEN'), 403);
        }

        $app   = $this->app ?? Factory::getApplication();
        $input = $app->getInput();
        $user  = $app->getIdentity();

        // Get POSTed data
        $data = (array) $input->post->get('jform', [], 'array');
        $rawData = $data;

        // Ensure we have context & item_id (from hidden fields / plugin)
        $context          = $data['context'] ?? $input->getString('context', 'com_content.article');
        $itemId           = (int) ($data['item_id'] ?? $input->getInt('item_id', 0));
        $data['context']  = $context;
        $data['item_id']  = $itemId;

        // Preferred redirect: exact page user posted from.
        $redirectUrl = $this->resolveReturnRedirectUrl($context, $itemId);

        /** @var \Joomla\Component\R3dcomments\Site\Model\CommentModel $model */
        $model = $this->getModel('Comment');

        // Form validation (JForm rules)
        $form = $model->getForm($data, false);

        if (!$form)
        {
            $app->enqueueMessage(Text::_('COM_R3DCOMMENTS_ERROR_FORM_NOT_AVAILABLE'), 'error');
            $this->setRedirect($redirectUrl);

            return;
        }

        // Validierung (mit unserer angepassten Logik im Model)
        $validData = $model->validate($form, $data);

        if ($validData === false)
        {
            $errors = $model->getErrors();

            foreach ($errors as $error)
            {
                $message = $error instanceof \Exception ? $error->getMessage() : (string) $error;
                $app->enqueueMessage($message, 'error');
            }

            $this->setRedirect($redirectUrl);

            return;
        }

        // Use validated form data as canonical payload.
        $data = (array) $validData;

        if ($user->guest)
        {
            try
            {
                // Spam protection depends on raw POST fields such as captcha and honeypot.
                $this->validateGuestSpamProtection($rawData);
            }
            catch (RuntimeException $e)
            {
                $app->enqueueMessage($e->getMessage(), 'error');
                $this->setRedirect($redirectUrl);

                return;
            }
        }

        // Try to save using our model logic
        try
        {
            // Moderations-Token für die E-Mail-Links generieren
            $data['moderation_token'] = bin2hex(Crypt::genRandomBytes(20)); // 40-stelliger Token

            $id = $model->save($data);

            $savedComment = $model->getItem($id);

            // Benachrichtigungen an Abonnenten senden, wenn der Kommentar veröffentlicht ist
            if ($savedComment && (int) ($savedComment->state ?? 0) === 1)
            {
                $this->sendSubscriptionEmails($id, (int) $user->id);
            }

            // E-Mail-Benachrichtigung senden
            $componentParams = Factory::getApplication()->getParams('com_r3dcomments');
            $recipient       = $componentParams->get('recipient_email');

            if (!empty($recipient))
            {
                $config = Factory::getConfig(); // Globale Joomla-Konfig für Mailer-Sender
                $mailer = Factory::getMailer();

                // Moderations-Links erstellen
                $baseUri    = Uri::root();
                $moderationReturnUrl = $this->buildContextRedirectUrl($context, $itemId);
                $publishUrl = $baseUri . 'index.php?option=com_r3dcomments&task=comment.moderate&id=' . $id . '&token=' . $data['moderation_token'] . '&action=publish&return=' . rawurlencode($moderationReturnUrl);
                $trashUrl   = $baseUri . 'index.php?option=com_r3dcomments&task=comment.moderate&id=' . $id . '&token=' . $data['moderation_token'] . '&action=trash&return=' . rawurlencode($moderationReturnUrl);
                $backendUrl = $baseUri . 'administrator/index.php?option=com_r3dcomments&task=comment.edit&id=' . $id;

                // Betreff und Body aus der Konfiguration holen
                $defaultSubject = 'New comment on {site_name}';
                $defaultBody    = "A new comment has been posted on the website.\n\nAuthor: {author_name}\nE-Mail: {author_email}\nComment:\n{comment}\n\nActions:\nPublish: {publish_url}\nTrash: {trash_url}\n\nEdit in backend:\n{backend_url}";

                $subjectTemplate = $componentParams->get('email_subject', $defaultSubject);
                $bodyTemplate    = $componentParams->get('email_body', $defaultBody);

                // Platzhalter-Daten vorbereiten
                $replacements = [
                    '{site_name}'    => $config->get('sitename'),
                    '{author_name}'  => $user->guest ? ($data['author_name'] ?? 'Gast') : $user->name,
                    '{author_email}' => $user->guest ? ($data['author_email'] ?? 'N/A') : $user->email,
                    '{comment}'      => $data['comment'] ?? '',
                    '{publish_url}'  => $publishUrl,
                    '{trash_url}'    => $trashUrl,
                    '{backend_url}'  => $backendUrl,
                ];

                $subject = str_replace(array_keys($replacements), array_values($replacements), $subjectTemplate);
                $body    = str_replace(array_keys($replacements), array_values($replacements), $bodyTemplate);

                $mailer->setSender([
                    $config->get('mailfrom'),
                    $config->get('fromname')
                ]);
                $mailer->addRecipient($recipient);
                $mailer->setSubject($subject);
                $mailer->setBody($body);
                $mailer->send();
            }
        }
        catch (RuntimeException $e)
        {
            $app->enqueueMessage($e->getMessage(), 'error');
            $this->setRedirect($redirectUrl);

            return;
        }

        // Erfolgsmeldung
        $message = $user->guest
            ? Text::_('COM_R3DCOMMENTS_DATA_FORM_INFORMATION_RECEIVED_PENDING')
            : Text::_('COM_R3DCOMMENTS_DATA_FORM_INFORMATION_RECEIVED');

        $this->setMessage($message);
        $this->setRedirect($redirectUrl);
    }

    protected function enforceGuestRateLimit(): void
    {
        $app    = $this->app ?? Factory::getApplication();
        $params = $app->getParams('com_r3dcomments');

        $maxSubmissions = (int) $params->get('guest_rate_limit_max', 3);
        $windowSeconds  = (int) $params->get('guest_rate_limit_window', 300);

        if ($maxSubmissions <= 0 || $windowSeconds <= 0)
        {
            return;
        }

        $ipHash = $this->buildIpHash();

        if ($ipHash === null)
        {
            return;
        }

        $db = Factory::getDbo();
        $cutoff = Factory::getDate()
            ->sub(new DateInterval('PT' . $windowSeconds . 'S'))
            ->toSql();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__r3dcomments'))
            ->where($db->quoteName('created_by') . ' = 0')
            ->where($db->quoteName('ip_hash') . ' = ' . $db->quote($ipHash))
            ->where($db->quoteName('created') . ' >= ' . $db->quote($cutoff))
            ->where($db->quoteName('state') . ' != -2');

        $db->setQuery($query);
        $submissionCount = (int) $db->loadResult();

        if ($submissionCount >= $maxSubmissions)
        {
            throw new RuntimeException(Text::sprintf(
                'COM_R3DCOMMENTS_ERROR_GUEST_RATE_LIMIT',
                $maxSubmissions,
                $windowSeconds
            ), 429);
        }
    }

    protected function enforceGuestRateLimitDay(): void
    {
        $app    = $this->app ?? Factory::getApplication();
        $params = $app->getParams('com_r3dcomments');

        $maxSubmissions = (int) $params->get('guest_rate_limit_day_max', 10);
        $windowSeconds  = (int) $params->get('guest_rate_limit_day_window', 86400);

        if ($maxSubmissions <= 0 || $windowSeconds <= 0)
        {
            return;
        }

        $ipHash = $this->buildIpHash();

        if ($ipHash === null)
        {
            return;
        }

        $db = Factory::getDbo();
        $cutoff = Factory::getDate()
            ->sub(new DateInterval('PT' . $windowSeconds . 'S'))
            ->toSql();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__r3dcomments'))
            ->where($db->quoteName('created_by') . ' = 0')
            ->where($db->quoteName('ip_hash') . ' = ' . $db->quote($ipHash))
            ->where($db->quoteName('created') . ' >= ' . $db->quote($cutoff))
            ->where($db->quoteName('state') . ' != -2');

        $db->setQuery($query);
        $submissionCount = (int) $db->loadResult();

        if ($submissionCount >= $maxSubmissions)
        {
            throw new RuntimeException(Text::sprintf(
                'COM_R3DCOMMENTS_ERROR_GUEST_RATE_LIMIT_DAY',
                $maxSubmissions
            ), 429);
        }
    }

    protected function validateGuestSpamProtection(array $data): void
    {
        $app    = $this->app ?? Factory::getApplication();
        $params = $app->getParams('com_r3dcomments');

        $honeypotEnabled = (int) $params->get('guest_honeypot_enabled', 1) === 1;
        $honeypotField   = trim((string) $params->get('guest_honeypot_field', 'website'));

        if ($honeypotEnabled && $honeypotField !== '')
        {
            $honeypotValue = trim((string) ($data[$honeypotField] ?? ''));

            if ($honeypotValue !== '')
            {
                throw new RuntimeException(Text::_('COM_R3DCOMMENTS_ERROR_SPAM_DETECTED'), 400);
            }
        }

        $minSubmitSeconds = (int) $params->get('guest_min_submit_seconds', 2);

        if ($minSubmitSeconds > 0)
        {
            $startedAt = (int) ($data['form_started_at'] ?? 0);
            $nowTs     = time();

            if ($startedAt <= 0 || ($nowTs - $startedAt) < $minSubmitSeconds)
            {
                throw new RuntimeException(Text::sprintf(
                    'COM_R3DCOMMENTS_ERROR_SUBMIT_TOO_FAST',
                    $minSubmitSeconds
                ), 429);
            }
        }

        $maxLinks = (int) $params->get('guest_max_links', 2);

        if ($maxLinks > 0)
        {
            $comment = (string) ($data['comment'] ?? '');
            $linkCount = $this->countLinks($comment);

            if ($linkCount > $maxLinks)
            {
                $action = (string) $params->get('guest_link_limit_action', 'moderate');

                if ($action === 'block')
                {
                    throw new RuntimeException(Text::sprintf(
                        'COM_R3DCOMMENTS_ERROR_TOO_MANY_LINKS',
                        $maxLinks
                    ), 400);
                }
            }
        }

        $this->validateGuestCaptcha($data);
        $this->enforceGuestRateLimit();
        $this->enforceGuestRateLimitDay();
    }

    protected function validateGuestCaptcha(array $data): void
    {
        $app    = $this->app ?? Factory::getApplication();
        $params = $app->getParams('com_r3dcomments');

        $captchaMode = (string) $params->get('guest_captcha_mode', 'off');

        if ($captchaMode !== 'always')
        {
            return;
        }

        $defaultCaptcha = (string) Factory::getConfig()->get('captcha', '');

        if ($defaultCaptcha === '' || $defaultCaptcha === '0')
        {
            return;
        }

        $captcha = Captcha::getInstance($defaultCaptcha, ['namespace' => 'plg_captcha_' . $defaultCaptcha]);
        $captchaResponse = $this->extractCaptchaResponse($data, $app->getInput());

        if (!$captcha || !$captcha->checkAnswer($captchaResponse))
        {
            throw new RuntimeException(Text::_('COM_R3DCOMMENTS_ERROR_INVALID_CAPTCHA'), 400);
        }
    }

    protected function extractCaptchaResponse(array $data, $input): ?string
    {
        $candidates = [
            $data['captcha'] ?? null,
            $data['altcha'] ?? null,
        ];

        if (is_object($input) && method_exists($input, 'get'))
        {
            $candidates[] = $input->post->getString('captcha', '');
            $candidates[] = $input->post->getString('altcha', '');
        }

        foreach ($candidates as $candidate)
        {
            if (is_string($candidate))
            {
                $candidate = trim($candidate);

                if ($candidate !== '')
                {
                    return $candidate;
                }
            }
        }

        return null;
    }

    protected function countLinks(string $comment): int
    {
        if ($comment === '')
        {
            return 0;
        }

        $links = 0;

        if (preg_match_all('/<a\s[^>]*href\s*=\s*["\']?[^"\'>\s]+/i', $comment, $matches)) {
            $links += count($matches[0]);
        }

        $plainText = strip_tags($comment);

        if (preg_match_all('/\b(?:https?:\/\/|www\.)\S+/i', $plainText, $matches)) {
            $links += count($matches[0]);
        }

        return $links;
    }

    protected function buildIpHash(): ?string
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

        if ($ip === '')
        {
            return null;
        }

        $secret = (string) Factory::getConfig()->get('secret', '');

        if ($secret === '')
        {
            return hash('sha256', $ip);
        }

        return hash_hmac('sha256', $ip, $secret);
    }

    /**
     * Send email notifications to all subscribers of a content item.
     */
    protected function sendSubscriptionEmails(int $commentId, int $authorId): void
    {
        $db = Factory::getDbo();
        
        // Kommentar-Details laden
        $commentQuery = $db->getQuery(true)
            ->select('a.comment, a.context, a.item_id, a.author_name AS guest_author_name')
            ->from($db->quoteName('#__r3dcomments', 'a'))
            ->where('a.id = ' . $commentId);
        $db->setQuery($commentQuery);
        $comment = $db->loadObject();

        if (!$comment) {
            return;
        }

        // Abonnenten laden (außer dem Autor des Kommentars)
        $subscribersQuery = $db->getQuery(true)
            ->select('u.email, u.name')
            ->from($db->quoteName('#__r3dcomments_subscriptions', 's'))
            ->join('INNER', $db->quoteName('#__users', 'u') . ' ON s.user_id = u.id')
            ->where('s.context = ' . $db->quote($comment->context))
            ->where('s.item_id = ' . (int) $comment->item_id)
            ->where('s.user_id <> ' . (int) $authorId);
        
        $db->setQuery($subscribersQuery);
        $subscribers = $db->loadObjectList();

        if (empty($subscribers)) {
            return;
        }

        $config    = Factory::getConfig();
        $mailer    = Factory::getMailer();
        $userFactory = Factory::getContainer()->get(UserFactoryInterface::class);
        $authorUser = $authorId > 0 ? $userFactory->loadUserById($authorId) : null;

        if (!$authorUser)
        {
            $authorUser = Factory::getApplication()->getIdentity();
        }
        $authorName = $authorUser->guest ? $comment->guest_author_name : $authorUser->name;

        $articleUrl = Uri::root() . ltrim(
            $this->buildContextRedirectUrl((string) $comment->context, (int) $comment->item_id),
            '/'
        );

        $subject = 'Neuer Kommentar zum Beitrag';
        $body = "Hallo {recipient_name},\n\n"
              . "Ein neuer Kommentar wurde von '{author_name}' zum Beitrag, den Sie abonniert haben, hinzugefügt:\n\n"
              . "--------------------------------------------------\n"
              . "{comment}\n"
              . "--------------------------------------------------\n\n"
              . "Sie können den Beitrag hier ansehen:\n"
              . "{article_url}";

        foreach ($subscribers as $subscriber) {
            $replacements = [
                '{recipient_name}' => $subscriber->name,
                '{author_name}'    => $authorName,
                '{comment}'        => $comment->comment,
                '{article_url}'    => $articleUrl,
            ];
            $emailBody = str_replace(array_keys($replacements), array_values($replacements), $body);

            $mailer->clearAllRecipients();
            $mailer->setSender([$config->get('mailfrom'), $config->get('fromname')]);
            $mailer->addRecipient($subscriber->email);
            $mailer->setSubject($subject);
            $mailer->setBody($emailBody);
            $mailer->send();
        }
    }

    public function toggleSubscription(): void
    {
        $app  = Factory::getApplication();
        $user = $app->getIdentity();

        if ($user->guest) {
            $app->enqueueMessage(Text::_('COM_R3DCOMMENTS_SUBSCRIPTION_LOGIN_REQUIRED'), 'error');
            $this->setRedirect(
                $this->buildContextRedirectUrl(
                    $this->input->getString('context', 'com_content.article'),
                    $this->input->getInt('item_id', 0)
                )
            );
            return;
        }

        $context = $this->input->getString('context');
        $itemId  = $this->input->getInt('item_id');
        $redirectUrl = $this->buildContextRedirectUrl($context, $itemId);

        /** @var \Joomla\Component\R3dcomments\Site\Model\SubscriptionModel $model */
        $model = $this->getModel('Subscription');

        try {
            $isSubscribed = $model->toggleSubscription($user->id, $context, $itemId);
            $message = $isSubscribed
                ? Text::_('COM_R3DCOMMENTS_SUBSCRIPTION_ENABLED')
                : Text::_('COM_R3DCOMMENTS_SUBSCRIPTION_DISABLED');

            $app->enqueueMessage($message, 'message');
        } catch (\Exception $e) {
            $app->enqueueMessage(
                Text::sprintf('COM_R3DCOMMENTS_SUBSCRIPTION_TOGGLE_FAILED', $e->getMessage()),
                'error'
            );
        }

        $this->setRedirect($redirectUrl);
    }

    protected function findItemIdForArticle(int $articleId): int
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('id')
            ->from('#__menu')
            ->where($db->quoteName('link') . ' LIKE ' . $db->quote('%option=com_content&view=article&id=' . $articleId . '%'))
            ->where('type = ' . $db->quote('component'))
            ->where('published = 1')
            ->order('id ASC');

        $db->setQuery($query);
        return (int) $db->loadResult();
    }

    /**
     * Build a frontend redirect URL for the given item context.
     */
    protected function buildContextRedirectUrl(string $context, int $itemId): string
    {
        if ($itemId <= 0) {
            return Route::_('index.php', false);
        }

        $langTag = (string) Factory::getApplication()->getLanguage()->getTag();
        $lang = $langTag !== '' ? strtolower(substr($langTag, 0, 2)) : '';

        if ($context === 'com_content.article') {
            $url = 'index.php?option=com_content&view=article&id=' . $itemId;
            $itemid = $this->findItemIdForArticle($itemId);

            if ($itemid > 0) {
                $url .= '&Itemid=' . $itemid;
            }

            if ($lang !== '') {
                $url .= '&lang=' . $lang;
            }

            return Route::_($url, false);
        }

        $parts = explode('.', $context);
        $option = $parts[0] ?? 'com_content';
        $view = $parts[1] ?? 'article';

        $url = 'index.php?option=' . rawurlencode($option)
            . '&view=' . rawurlencode($view)
            . '&id=' . $itemId;

        if ($lang !== '') {
            $url .= '&lang=' . $lang;
        }

        return Route::_($url, false);
    }

    /**
     * Resolve redirect target after form submit.
     * Prefers explicit return URL from form and falls back to context-based route.
     */
    protected function resolveReturnRedirectUrl(string $context, int $itemId): string
    {
        $input = ($this->app ?? Factory::getApplication())->getInput();
        $return = trim((string) $input->post->getString('return', ''));

        if ($return !== '')
        {
            // The form stores the current page URL as plain text and we only
            // accept internal targets here to avoid open redirects.
            if (Uri::isInternal($return))
            {
                return Route::_($return, false);
            }
        }

        return $this->buildContextRedirectUrl($context, $itemId);
    }

    public function moderate(): void
    {
        $app   = $this->app ?? Factory::getApplication();
        $input = $app->getInput();

        $id     = $input->getInt('id');
        $token  = $input->getString('token');
        $action = $input->getString('action');
        $return = trim((string) $input->getString('return', ''));

        if (!$id || !$token || !$action)
        {
            $app->enqueueMessage(Text::_('COM_R3DCOMMENTS_MODERATION_INVALID_LINK'), 'error');
            $this->setRedirect(Route::_('index.php', false));
            return;
        }

        /** @var \Joomla\Component\R3dcomments\Site\Model\CommentModel $model */
        $model   = $this->getModel('Comment');
        $comment = $model->getItem($id);

        if (!$comment || empty($comment->moderation_token) || !hash_equals($comment->moderation_token, $token))
        {
            $app->enqueueMessage(Text::_('COM_R3DCOMMENTS_MODERATION_LINK_INVALID_OR_USED'), 'error');
            $this->setRedirect(Route::_('index.php', false));
            return;
        }

        $redirectUrl = $this->buildContextRedirectUrl((string) $comment->context, (int) $comment->item_id);

        if ($return !== '' && Uri::isInternal($return))
        {
            $redirectUrl = Route::_($return, false);
        }

        $newState = null;
        $message  = '';

        if ($action === 'publish')
        {
            $newState = 1;
            $message  = Text::_('COM_R3DCOMMENTS_MODERATION_PUBLISH_SUCCESS');
        }
        elseif ($action === 'trash')
        {
            $newState = -2;
            $message  = Text::_('COM_R3DCOMMENTS_MODERATION_TRASH_SUCCESS');
        }

        if ($newState !== null)
        {
            $table = $model->getTable('Comment', 'Administrator');
            $table->publish([$id], $newState);
            $app->enqueueMessage($message, 'message');
        }

        $this->setRedirect($redirectUrl);
    }
}
