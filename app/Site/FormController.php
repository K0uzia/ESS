<?php
declare(strict_types=1);

namespace App\Site;

use App\Content\Page;
use App\Core\Db;
use App\Core\FormGuard;
use App\Core\Mailer;
use App\Core\View;

final class FormController
{
    private const MESSAGE_MAX = 5000;

    public static function contact(): void
    {
        $values = [
            'nom' => trim((string) ($_POST['nom'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'pole' => (string) ($_POST['pole'] ?? ''),
            'message' => trim((string) ($_POST['message'] ?? '')),
        ];
        $errors = [];
        if ($values['nom'] === '' || text_length($values['nom']) > 120) {
            $errors['nom'] = 'Indiquez votre nom (120 caractères au plus).';
        }
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Indiquez une adresse e-mail valide, par exemple nom@exemple.fr.';
        }
        // Destinataire obligatoire : un organisme (pôle, ESS29 ou TAg29) dont l'adresse de réception est réglée.
        $recipients = self::contactRecipients();
        $recipient = $recipients[$values['pole']]['name'] ?? null;
        if ($recipient === null) {
            $errors['pole'] = 'Choisissez à qui envoyer votre message : un pôle, ESS29 ou TAg29.';
        }
        if (text_length($values['message']) < 10 || text_length($values['message']) > self::MESSAGE_MAX) {
            $errors['message'] = 'Écrivez votre message (entre 10 et ' . self::MESSAGE_MAX . ' caractères).';
        }
        if ($errors === [] && FormGuard::rateLimited('contact', 5, 60)) {
            $errors['form'] = 'Trop de messages envoyés depuis votre connexion. Réessayez dans une heure ou écrivez directement à ' . setting('email_contact') . '.';
        }
        if ($errors !== []) {
            self::renderWithErrors('/', 'accueil', $errors, $values);
            return;
        }

        if (FormGuard::expired()) {
            self::renderWithErrors('/', 'accueil', ['form' => 'La page est restée ouverte longtemps : votre message n\'est pas encore parti. Vérifiez-le puis cliquez de nouveau sur « Envoyer le message ».'], $values);
            return;
        }
        // Le site ne garde aucune copie : le message n'existe que dans l'e-mail envoyé au destinataire choisi.
        $to = $recipients[$values['pole']]['to'];
        if (FormGuard::passes()) {
            $sent = Mailer::send(
                $to,
                'Message du site ess29.org (' . $recipient . ') : ' . $values['nom'],
                "Nom : {$values['nom']}\nE-mail : {$values['email']}\nDestinataire : $recipient\n\n{$values['message']}\n",
                null,
                $values['email']
            );
            if (!$sent) {
                self::renderWithErrors('/', 'accueil', ['form' => 'L\'envoi a échoué de notre côté. Réessayez dans quelques minutes ou écrivez directement à ' . $to . '.'], $values);
                return;
            }
        }
        redirect('/?envoye=1#contact');
    }

    public static function subscribe(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $chosen = array_values(array_filter((array) ($_POST['lettres'] ?? []), 'is_string'));
        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Indiquez une adresse e-mail valide, par exemple nom@exemple.fr.';
        }
        $lists = array_values(array_filter(self::publicLists(), static fn (array $l): bool => in_array($l['org'], $chosen, true)));
        if ($lists === []) {
            $errors['lettres'] = 'Choisissez au moins une lettre d\'information.';
        }
        if ($errors === [] && FormGuard::rateLimited('newsletter', 5, 60)) {
            $errors['form'] = 'Trop de demandes depuis votre connexion. Réessayez dans une heure.';
        }
        $back = (string) ($_POST['retour'] ?? '/');
        $back = isset(SiteController::PAGES[$back]) ? $back : '/';
        if ($errors !== []) {
            self::renderWithErrors($back, SiteController::PAGES[$back], $errors, ['email' => $email, 'lettres' => $chosen]);
            return;
        }
        if (FormGuard::expired()) {
            self::renderWithErrors($back, SiteController::PAGES[$back], ['form' => 'La page est restée ouverte longtemps : votre inscription n\'est pas encore enregistrée. Cliquez de nouveau sur le bouton d\'inscription.'], ['email' => $email, 'lettres' => $chosen]);
            return;
        }

        if (FormGuard::passes()) {
            $confirm = bin2hex(random_bytes(24));
            $pending = [];
            foreach ($lists as $l) {
                $row = Db::run('SELECT status FROM newsletter_subscribers WHERE list_id = ? AND email = ?', [$l['id'], $email])->fetch();
                if ($row && $row['status'] === 'active') {
                    continue;
                }
                if ($row) {
                    Db::run(
                        "UPDATE newsletter_subscribers SET status = 'pending', confirm_token = ?, consent_at = CURRENT_TIMESTAMP, unsubscribed_at = NULL WHERE list_id = ? AND email = ?",
                        [$confirm, $l['id'], $email]
                    );
                } else {
                    Db::run(
                        'INSERT INTO newsletter_subscribers (list_id, email, token, confirm_token) VALUES (?, ?, ?, ?)',
                        [$l['id'], $email, bin2hex(random_bytes(24)), $confirm]
                    );
                }
                $pending[] = 'la lettre ' . ($l['org'] === 'ess29' || $l['org'] === 'tag29' ? 'de ' : 'du ') . $l['org_name'];
            }
            if ($pending !== []) {
                $link = rtrim(setting('site_url'), '/') . '/newsletter/confirmer?jeton=' . $confirm;
                if (($_ENV['APP_ENV'] ?? '') === 'local') {
                    $link = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8080', '/') . '/newsletter/confirmer?jeton=' . $confirm;
                }
                Mailer::send(
                    $email,
                    'Confirmez votre inscription aux lettres d\'information ESS Finistère',
                    "Bonjour,\n\nVous avez demandé à recevoir : " . implode(', ', $pending) . ".\n\n"
                    . "Pour confirmer, ouvrez ce lien :\n$link\n\n"
                    . "Si vous n'êtes pas à l'origine de cette demande, ignorez ce message : aucune inscription ne sera faite.\n\n"
                    . setting('raison_sociale') . "\n"
                );
            }
        }
        // Même réponse que l'adresse soit nouvelle, déjà inscrite ou issue d'un robot.
        redirect('/newsletter/?statut=demande');
    }

    public static function confirm(): void
    {
        $token = (string) ($_GET['jeton'] ?? '');
        $count = 0;
        if (preg_match('/^[a-f0-9]{48}$/', $token)) {
            $count = Db::run(
                "UPDATE newsletter_subscribers SET status = 'active', confirmed_at = CURRENT_TIMESTAMP, confirm_token = NULL
                 WHERE confirm_token = ? AND status = 'pending' AND consent_at >= datetime('now', '-7 days')",
                [$token]
            )->rowCount();
        }
        redirect('/newsletter/?statut=' . ($count > 0 ? 'confirme' : 'lien-invalide'));
    }

    /** GET affiche un bouton (les antivirus de messagerie suivent les liens) ; POST désinscrit, y compris en un clic (RFC 8058). */
    public static function unsubscribe(): void
    {
        $token = (string) ($_GET['jeton'] ?? $_POST['jeton'] ?? '');
        $sub = preg_match('/^[a-f0-9]{48}$/', $token)
            ? Db::run('SELECT s.* FROM newsletter_subscribers s WHERE s.token = ?', [$token])->fetch()
            : false;
        if ($sub) {
            $sub['org_name'] = Orgs::findList((int) $sub['list_id'])['org_name'] ?? '';
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if ($sub) {
                Db::run("UPDATE newsletter_subscribers SET status = 'unsubscribed', unsubscribed_at = CURRENT_TIMESTAMP WHERE id = ?", [$sub['id']]);
            }
            redirect('/newsletter/?statut=desinscrit');
        }
        self::newsletterPage($sub ? 'desinscription' : 'lien-invalide', $sub ?: null);
    }

    public static function newsletter(): void
    {
        self::newsletterPage((string) ($_GET['statut'] ?? ''), null);
    }

    /** @param array<string, mixed>|null $subscriber */
    private static function newsletterPage(string $status, ?array $subscriber): void
    {
        $messages = [
            'demande' => 'Merci. Si l\'adresse n\'était pas encore inscrite, un e-mail de confirmation vient de lui être envoyé. L\'inscription ne sera faite qu\'après avoir cliqué sur le lien qu\'il contient (valable 7 jours).',
            'confirme' => 'Votre inscription est confirmée. Vous pouvez vous désinscrire à tout moment grâce au lien présent dans chaque lettre.',
            'lien-invalide' => 'Ce lien n\'est plus valable. Il a peut-être expiré ou déjà été utilisé. Vous pouvez refaire une demande d\'inscription ci-dessous.',
            'desinscrit' => 'Votre désinscription est enregistrée. Vous ne recevrez plus cette lettre.',
        ];
        header('X-Robots-Tag: noindex');
        View::render('site/newsletter', [
            'path' => '/newsletter/',
            'pageKey' => 'newsletter',
            'page' => ['meta' => [
                'title' => 'Lettres d\'information',
                'description' => '',
                'h1' => 'Lettres d\'information',
            ], 'sections' => []],
            'breadcrumb' => [['/', 'Accueil']],
            'status' => $status,
            'message' => $messages[$status] ?? null,
            'subscriber' => $subscriber,
        ], 'site/layout');
    }

    /**
     * Destinataires possibles du formulaire de contact : organismes dont l'adresse de réception est réglée.
     * @return array<string, array{name: string, to: string}>
     */
    public static function contactRecipients(): array
    {
        $out = [];
        foreach (Orgs::all() as $key => $org) {
            $to = Orgs::address($key, 'contact');
            if ($to !== '') {
                $out[$key] = ['name' => $org['name'], 'to' => $to];
            }
        }
        return $out;
    }

    /**
     * Lettres proposées au public : listes « inscrits via le site » ouvertes, une par organisme.
     * @return list<array<string, mixed>>
     */
    public static function publicLists(): array
    {
        return array_values(array_filter(Orgs::lists(), static fn (array $l): bool => $l['audience'] === 'inscrits'));
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed> $values
     */
    private static function renderWithErrors(string $path, string $pageKey, array $errors, array $values): void
    {
        http_response_code(422);
        $GLOBALS['form_state'] = ['errors' => $errors, 'values' => $values];
        View::render('site/page', [
            'path' => $path,
            'pageKey' => $pageKey,
            'page' => Page::load($pageKey) ?? ['meta' => ['h1' => ''], 'sections' => []],
            'breadcrumb' => [],
        ], 'site/layout');
    }
}
