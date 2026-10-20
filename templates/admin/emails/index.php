<?php
use App\Admin\Ui;
use App\Core\Auth;
use App\Core\Csrf;
use App\Site\Components;

$me = Auth::user();
$boxes = array_values(array_filter($senders, static fn (array $s): bool => $s['box'] === ''));
$byEmail = array_change_key_case(array_column($senders, null, 'email'));
// État de connexion d'une boîte : vérifiée à l'enregistrement du mot de passe, puis à chaque envoi.
$state = static function (?array $box): string {
    return match (true) {
        $box === null || $box['password'] === '' => '<span class="badge badge--warn">mot de passe manquant</span>',
        App\Core\Mailer::unreadable($box['password']) => '<span class="badge badge--warn">' . Ui::icon('alert') . 'mot de passe à saisir à nouveau</span>',
        $box['checked_at'] === null => '<span class="badge badge--off">connexion pas encore vérifiée</span>',
        $box['check_error'] !== '' => '<span class="badge badge--warn">' . Ui::icon('alert') . 'non connectée</span>',
        default => '<span class="badge badge--on">' . Ui::icon('check') . 'connectée</span>',
    };
};
// Une ligne « usage → adresse ». Pour une adresse qui envoie, sa nature (boîte, alias) et ce qui bloque l'envoi.
$line = static function (string $usage, string $email, bool $sends = false, ?string $closed = null) use ($byEmail, $state): string {
    if ($closed !== null) {
        $value = '<span class="muted">' . e($closed) . '</span>';
    } elseif ($email === '') {
        $value = '<span class="badge badge--warn">À régler</span>';
    } else {
        $value = '<strong class="chip">' . e($email) . '</strong>';
        if ($sends) {
            $s = $byEmail[strtolower($email)] ?? null;
            $value .= match (true) {
                $s === null => ' <span class="badge badge--warn">reliée à aucune boîte : rien ne part</span>',
                $s['box'] !== '' => ' <span class="muted">alias de ' . e($s['box']) . '</span> ' . $state($byEmail[strtolower($s['box'])] ?? null),
                default => ' <span class="muted">boîte</span> ' . $state($s),
            };
        }
    }
    return '<li class="rows__item rows__item--map"><span class="rows__key">' . e($usage) . '</span><span class="rows__arrow" aria-hidden="true">→</span><span class="rows__value">' . $value . '</span></li>';
};
?>
<header class="page-head">
  <div class="page-head__text">
    <h1>E-mails</h1>
    <p class="page-head__sub">Quelle adresse sert à quoi, pour tout le site et pour chaque organisme. Les adresses qui envoient se relient à une boîte Hostinger en bas de page.</p>
  </div>
</header>

<?php if ($boxes === []): ?>
<div class="notice notice--warning"><?= Ui::icon('alert') ?><p>Aucune boîte d'envoi n'est réglée : le site n'envoie aucun e-mail (invitations, formulaire de contact, newsletters). <a href="/admin/emails/boites/nouvelle" data-modal>Ajouter la boîte Hostinger</a></p></div>
<?php endif ?>
<?php foreach ($boxes as $b): if ($b['password'] === ''): ?>
<div class="notice notice--warning"><?= Ui::icon('alert') ?><p>Le mot de passe de la boîte <strong><?= e($b['email']) ?></strong> n'est pas enregistré : rien ne part depuis elle ni depuis ses alias. <a href="/admin/emails/boites/<?= (int) $b['id'] ?>" data-modal>Saisir le mot de passe</a></p></div>
<?php elseif (App\Core\Mailer::unreadable($b['password'])): ?>
<div class="notice notice--warning"><?= Ui::icon('alert') ?><p>Le mot de passe de la boîte <strong><?= e($b['email']) ?></strong> ne peut pas être lu sur ce serveur (enregistré ailleurs ou avec une autre clé) : rien ne part depuis elle ni depuis ses alias. <a href="/admin/emails/boites/<?= (int) $b['id'] ?>" data-modal>Saisir à nouveau le mot de passe</a></p></div>
<?php elseif ($b['checked_at'] !== null && $b['check_error'] !== ''): ?>
<div class="notice notice--warning"><?= Ui::icon('alert') ?><p>La boîte <strong><?= e($b['email']) ?></strong> n'est pas connectée : <?= e(App\Core\Mailer::explainCheck($b['check_error'])) ?> (le <?= e(Ui::date($b['checked_at'], true)) ?>). Rien ne part depuis elle ni depuis ses alias. <a href="/admin/emails/boites/<?= (int) $b['id'] ?>" data-modal>Saisir à nouveau le mot de passe</a></p></div>
<?php endif; endforeach ?>
<?php foreach ($missing as $email => $what): ?>
<div class="notice notice--warning"><?= Ui::icon('alert') ?><p><strong><?= e($email) ?></strong> doit envoyer (<?= e(App\Admin\EmailController::usesText($what)) ?>) mais n'est reliée à aucune boîte : rien ne part. <a href="/admin/emails/boites/nouvelle?email=<?= e(rawurlencode($email)) ?>" data-modal>La relier à une boîte</a></p></div>
<?php endforeach ?>

<section class="card card--flush group-card" id="site" aria-labelledby="site-titre">
  <div class="group-card__head">
    <h2 class="group-card__title" id="site-titre"><img class="org-logo" src="/assets/img/logo-ess-finistere-couleur.webp" alt="">Tout le site</h2>
    <a class="btn" href="/admin/emails/site" data-modal><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> : tout le site</span></a>
  </div>
  <ul class="rows" role="list">
    <?= $line('Envoie les invitations et mots de passe des comptes', setting('email_comptes'), true) ?>
    <?= $line('Envoie les e-mails automatiques (contact transmis, confirmations d\'inscription)', setting('email_envoi'), true) ?>
  </ul>
</section>

<?php foreach ($orgs as $key => $o): ?>
<section class="card card--flush group-card" id="org-<?= e($key) ?>" aria-labelledby="org-<?= e($key) ?>-titre">
  <div class="group-card__head">
    <?php $logo = Components::teamLogo($key === 'ess29' ? 'groupement' : $key); ?>
    <h2 class="group-card__title" id="org-<?= e($key) ?>-titre"><?php if ($logo !== null): ?><img class="org-logo" src="<?= e($logo) ?>" alt=""><?php endif ?><?= e($o['name']) ?></h2>
    <a class="btn" href="/admin/emails/organismes/<?= e($key) ?>" data-modal><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> : <?= e($o['name']) ?></span></a>
  </div>
  <ul class="rows" role="list">
    <?= $line('Reçoit le formulaire de contact', $o['contact']) ?>
    <?= $line('Reçoit les réponses aux newsletters', $o['reponse']) ?>
    <?php if ($o['public'] !== null): ?><?= $line('Affichée sur le site', $o['public']) ?><?php endif ?>
    <?php foreach ($o['lists'] as $l): $name = $l['audience'] === 'adherents' ? 'Newsletter adhérents' : 'Newsletter inscrits via le site'; ?>
    <?php if (!$l['is_open']): ?>
    <?= $line($name, '', false, 'fermée, pas de newsletter') ?>
    <?php elseif ($l['sympa_address'] !== ''): ?>
    <?= $line($name . ' : liste Sympa', $l['sympa_address']) ?>
    <?= $line($name . ' : envoyée depuis', $l['sender_email'], true) ?>
    <?php else: ?>
    <?= $line($name . ' : envoyée depuis', $l['sender_email'], true) ?>
    <?php endif ?>
    <?php endforeach ?>
  </ul>
</section>
<?php endforeach ?>

<section class="card card--flush group-card" id="boites" aria-labelledby="boites-titre">
  <div class="group-card__head">
    <div>
      <h2 class="group-card__title" id="boites-titre"><?= Ui::icon('send') ?>Réglage technique : boîtes et alias d'envoi</h2>
      <p class="muted">Une boîte mail Hostinger se connecte avec son mot de passe. Un alias envoie à travers la boîte dont il dépend ; il doit exister comme alias chez Hostinger.</p>
    </div>
    <a class="btn btn--primary" href="/admin/emails/boites/nouvelle" data-modal><?= Ui::icon('plus') ?>Ajouter</a>
  </div>
  <?php if ($senders === []): ?>
  <p class="group-card__empty">Aucune adresse pour l'instant.</p>
  <?php else: ?>
  <ul class="rows" role="list">
    <?php foreach ($senders as $s): $isBox = $s['box'] === ''; ?>
    <li class="rows__item<?= $isBox ? '' : ' rows__item--sub' ?>">
      <a class="rows__main rows__main--stack" href="/admin/emails/boites/<?= (int) $s['id'] ?>" data-modal>
        <?php $b = $isBox ? $s : ($byEmail[strtolower($s['box'])] ?? null); ?>
        <span class="rows__line"><span class="rows__name"><?= e($s['email']) ?></span><span class="badge badge--off"><?= $isBox ? 'Boîte' : 'Alias' ?></span><?= $state($b) ?></span>
        <span class="rows__detail"><?= $isBox ? 'Boîte mail Hostinger' : 'Alias de ' . e($s['box']) ?><?php if ($b !== null && $b['password'] !== '' && $b['checked_at'] !== null): ?> · <?= $b['check_error'] === '' ? 'connexion acceptée' : e(App\Core\Mailer::explainCheck($b['check_error'])) ?> le <?= e(Ui::date($b['checked_at'], true)) ?><?php endif ?></span>
      </a>
      <form method="post" action="/admin/emails/boites/<?= (int) $s['id'] ?>/test">
        <?= Csrf::field() ?>
        <button class="btn btn--ghost" type="submit"<?= empty($me['email']) ? ' disabled' : '' ?>><?= Ui::icon('send') ?>Tester<span class="visually-hidden"> : m'envoyer un e-mail depuis <?= e($s['email']) ?></span></button>
      </form>
      <a class="btn" href="/admin/emails/boites/<?= (int) $s['id'] ?>" data-modal><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> <?= e($s['email']) ?></span></a>
      <form method="post" action="/admin/emails/boites/<?= (int) $s['id'] ?>/suppression" data-confirm="Retirer <?= e($s['email']) ?> de la liste ?">
        <?= Csrf::field() ?><button class="btn btn--danger" type="submit"><?= Ui::icon('trash') ?><span class="visually-hidden">Retirer <?= e($s['email']) ?></span></button>
      </form>
    </li>
    <?php endforeach ?>
  </ul>
  <?php endif ?>
</section>
