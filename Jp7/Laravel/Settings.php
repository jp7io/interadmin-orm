<?php
/*
LARAVEL 4
*/
namespace Jp7\Laravel;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Jp7\Interadmin\DynamicLoader;
use Jp7\ExceptionHandler;

class Settings
{
    public static function apply()
    {
        self::errorHandling();
        self::checkConfig();

        if (\Schema::hasTable('_tipos')) {
            spl_autoload_register([DynamicLoader::class, 'load']);
        }

        BladeExtension::apply();
        self::testingEnv();
        self::extendWhoops();
        self::extendFormer();
        self::extendView();
        self::clearInterAdminCache();
    }

    public static function checkConfig()
    {
        if (\Config::get('app.url') != \InterSite::config()->url) {
            throw new \Exception('Check ./app/config/app.php (or ./app/config/local/app.php): '.
                \Config::get('app.url').' != '.\InterSite::config()->url);
        }
    }

    public static function testingEnv()
    {
        if (\App::environment('testing')) {
            // Filters are disabled by default
            \Route::enableFilters();

            // Bug former with phpunit
            if (!\Request::hasSession()) {
                \Request::setSession(\App::make('session.store'));
            }
        }
    }

    public static function errorHandling()
    {
        if (PHP_SAPI !== 'cli') {
            \App::error(function (\Exception $exception, $code) {
                \Log::error($exception);

                if (\App::environment('production')) {
                    ExceptionHandler::handle($exception);

                    return error_controller('error');
                }
            });

            // POST em pagina que aceita GET
            \App::error(function (MethodNotAllowedHttpException $exception, $code) {
                return error_controller('error');
            });

            // Metodo nao encontrado
            \App::error(function (\ReflectionException $exception, $code) {
                return error_controller('missing');
            });

            \App::error(function (ModelNotFoundException $exception, $code) {
                return error_controller('missing');
            });

            \App::missing(function ($exception) {
                if (in_array(\Request::segment(1), ['assets', 'imagecache'])) {
                    return \Response::make('404 - Not found', 404);
                }

                return error_controller('missing');
            });
        }
    }

    public static function extendFormer()
    {
        \App::before(function ($request) {
            // Needed for tests
            \Former::getFacadeRoot()->ids = [];
        });

        \Former::framework('TwitterBootstrap3');
        \Former::setOption('default_form_type', 'vertical');

        if ($dispatcher = \App::make('former.dispatcher')) {
            $dispatcher->addRepository('Jp7\\Former\\Fields\\');
        }
    }

    public static function extendView()
    {
        \View::composer('*', function ($view) {
            $parts = explode('.', $view->getName());
            array_pop($parts);
            \View::share('viewPath', implode('.', $parts));
        });
    }

    public static function extendWhoops()
    {
        if (\Request::ajax() || PHP_SAPI === 'cli') {
            return;
        }
        if (\App::bound('whoops')) {
            $whoops = \App::make('whoops');

            $whoops->pushHandler(function ($exception, $exceptionInspector, $runInstance) {
                ?>
				<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
				<style>
				.frame.app {
      				background-color: #ffeeee;
      			}
				</style>
				<script>
				setTimeout(function() {
					$('.frame:contains("app")').addClass('app');
				}, 200);
				</script>
				<?php
            });
        }
    }

    public static function clearInterAdminCache()
    {
        if (!\App::environment('local')) {
            return;
        }
        // Atualiza classmap e routes com CMD+SHIFT+R ou no terminal
        if (PHP_SAPI === 'cli' || \Request::server('HTTP_CACHE_CONTROL') === 'no-cache') {
            \Cache::forget('Interadmin.routes');
            \Cache::forget('Interadmin.classMap');
        }
    }
}
