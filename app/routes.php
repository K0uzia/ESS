<?php
declare(strict_types=1);

use App\Admin\AccountController;
use App\Admin\ContentController;
use App\Admin\MediaController;
use App\Admin\NewsletterController;
use App\Admin\ResourceController;
use App\Admin\Resources;
use App\Admin\AuthController;
use App\Admin\DashboardController;
use App\Core\Router;
use App\Site\Discovery;
use App\Site\FormController;
use App\Site\SiteController;

$router = new Router();
$router->notFound = [SiteController::class, 'notFound'];

foreach (array_keys(SiteController::PAGES) as $pagePath) {
    $router->get($pagePath, static function () use ($pagePath): void {
        SiteController::show($pagePath) || SiteController::notFound();
    });
}
$router->post('/contact/', [FormController::class, 'contact']);
$router->get('/newsletter/', [FormController::class, 'newsletter']);
$router->post('/newsletter/', [FormController::class, 'subscribe']);
$router->get('/newsletter/confirmer', [FormController::class, 'confirm']);
$router->get('/newsletter/desinscription', [FormController::class, 'unsubscribe']);
$router->post('/newsletter/desinscription', [FormController::class, 'unsubscribe']);
$router->get('/robots.txt', [Discovery::class, 'robots']);
$router->get('/sitemap.xml', [Discovery::class, 'sitemap']);
$router->get('/llms.txt', [Discovery::class, 'llms']);

$router->get('/admin/connexion', [AuthController::class, 'form']);
$router->post('/admin/connexion', [AuthController::class, 'login']);
$router->post('/admin/deconnexion', [AuthController::class, 'logout']);
$router->get('/admin/mot-de-passe/oublie', [AuthController::class, 'forgotForm']);
$router->post('/admin/mot-de-passe/oublie', [AuthController::class, 'forgot']);
$router->get('/admin/mot-de-passe', [AuthController::class, 'resetForm']);
$router->post('/admin/mot-de-passe', [AuthController::class, 'reset']);
$router->get('/admin/', [DashboardController::class, 'index']);

$router->get('/admin/comptes', [AccountController::class, 'index']);
$router->get('/admin/comptes/nouveau', [AccountController::class, 'create']);
$router->post('/admin/comptes', [AccountController::class, 'store']);
$router->get('/admin/comptes/{id}', [AccountController::class, 'edit']);
$router->post('/admin/comptes/{id}', [AccountController::class, 'update']);
$router->post('/admin/comptes/{id}/suppression', [AccountController::class, 'destroy']);
$router->get('/admin/comptes/{id}/mot-de-passe', [AccountController::class, 'passwordForm']);
$router->post('/admin/comptes/{id}/mot-de-passe', [AccountController::class, 'passwordUpdate']);

$router->get('/admin/pages', [ContentController::class, 'index']);
$router->get('/admin/pages/{key}', [ContentController::class, 'edit']);
$router->get('/admin/pages/{key}/en-tete', [ContentController::class, 'head']);
$router->post('/admin/pages/{key}/en-tete', [ContentController::class, 'headUpdate']);
$router->get('/admin/pages/{key}/sections/{section}', [ContentController::class, 'section']);
$router->post('/admin/pages/{key}/sections/{section}', [ContentController::class, 'sectionUpdate']);



$router->get('/admin/medias', [MediaController::class, 'index']);
$router->get('/admin/medias/nouvelle', [MediaController::class, 'create']);
$router->post('/admin/medias', [MediaController::class, 'store']);
$router->get('/admin/medias/{id}', [MediaController::class, 'edit']);
$router->post('/admin/medias/{id}', [MediaController::class, 'update']);
$router->post('/admin/medias/{id}/suppression', [MediaController::class, 'destroy']);

$router->get('/admin/newsletters', [NewsletterController::class, 'index']);
$router->get('/admin/newsletters/abonnes', [NewsletterController::class, 'subscribers']);
$router->post('/admin/newsletters/abonnes', [NewsletterController::class, 'addSubscriber']);
$router->get('/admin/newsletters/abonnes/export', [NewsletterController::class, 'export']);
$router->post('/admin/newsletters/abonnes/import', [NewsletterController::class, 'import']);
$router->post('/admin/newsletters/abonnes/{id}/suppression', [NewsletterController::class, 'deleteSubscriber']);
$router->get('/admin/newsletters/modeles/nouveau', [NewsletterController::class, 'templateForm']);
$router->post('/admin/newsletters/modeles', [NewsletterController::class, 'templateSave']);
$router->get('/admin/newsletters/modeles/{id}', [NewsletterController::class, 'templateForm']);
$router->post('/admin/newsletters/modeles/{id}', [NewsletterController::class, 'templateSave']);
$router->post('/admin/newsletters/modeles/{id}/suppression', [NewsletterController::class, 'templateDelete']);
$router->get('/admin/newsletters/campagnes/nouvelle', [NewsletterController::class, 'campaignForm']);
$router->post('/admin/newsletters/campagnes', [NewsletterController::class, 'campaignSave']);
$router->get('/admin/newsletters/campagnes/{id}', [NewsletterController::class, 'campaignForm']);
$router->post('/admin/newsletters/campagnes/{id}', [NewsletterController::class, 'campaignSave']);
$router->get('/admin/newsletters/campagnes/{id}/apercu', [NewsletterController::class, 'preview']);
$router->post('/admin/newsletters/campagnes/{id}/test', [NewsletterController::class, 'test']);
$router->post('/admin/newsletters/campagnes/{id}/envoi', [NewsletterController::class, 'send']);
$router->post('/admin/newsletters/campagnes/{id}/suppression', [NewsletterController::class, 'campaignDelete']);

$router->get('/admin/adresses', [ResourceController::class, 'addresses']);

// Modules simples (Resources) : une route par clé, pour ne pas capturer les autres chemins de l'admin.
foreach (array_keys(Resources::all()) as $resource) {
    $router->get("/admin/$resource", static fn () => ResourceController::index($resource));
    $router->get("/admin/$resource/nouveau", static fn () => ResourceController::create($resource));
    $router->post("/admin/$resource", static fn () => ResourceController::store($resource));
    $router->get("/admin/$resource/{id}", static fn (string $id) => ResourceController::edit($resource, $id));
    $router->post("/admin/$resource/{id}", static fn (string $id) => ResourceController::update($resource, $id));
    $router->post("/admin/$resource/{id}/suppression", static fn (string $id) => ResourceController::destroy($resource, $id));
    $router->post("/admin/$resource/{id}/deplacer", static fn (string $id) => ResourceController::move($resource, $id));
}

return $router;
