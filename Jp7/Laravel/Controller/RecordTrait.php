<?php

namespace Jp7\Laravel\Controller;

use Jp7\Laravel\RouterFacade as Router;
use Jp7\Interadmin\Record;
use ReflectionMethod;
use Exception;

trait RecordTrait
{
    /**
     * @var \Jp7\Interadmin\Query\Base
     */
    protected $scope = null;
    protected $recordActions = ['show', 'edit', 'update', 'destroy'];
    /**
     * @var array   Everything except /create, /edit and custom actions
     */
    protected $implicitNamedActions = ['index', 'store', 'show', 'update', 'destroy'];

    public function constructRecordTrait()
    {
        $this->beforeFilter('@setScope');
        $this->beforeFilter('@setType');
        $this->beforeFilter('@setRecord', ['only' => $this->recordActions]);
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function setScope($route)
    {
        $uri = $this->_getResourceUri($route);

        $breadcrumb = Router::uriToBreadcrumb($uri, function ($type, $segment) use ($route) {
            $slug = $route->getParameter(trim($segment, '{}'));

            return $type->records()->findOrFail($slug);
        });

        if ($type = end($breadcrumb)) {
            $parent = $type->getParent();
            if ($parent instanceof Record && !$parent->hasChildrenTipo($type->id_tipo)) {
                throw new Exception('It seems this route has a special structure.'.
                    ' You need to define a custom setScope() to handle this.');
            }
            $this->scope = $type->records();
        }
    }

    protected function _getResourceUri($route)
    {
        $uri = $route->getUri();

        if (!in_array($this->action, $this->implicitNamedActions)) {
            $uri = dirname($uri); // Remove extra directory
        }
        if ($this->isRecordAction()) {
            $uri = dirname($uri); // Do not resolve $record yet
        }

        return $uri;
    }

    public function setType()
    {
        if (!$this->scope) {
            throw new Exception('setScope() could not resolve the'
                .' type associated with this URI. You need to map it on routes.php.'
                .' You can also define a custom setScope() or setType()');
        }
        $this->type = $this->scope->type();
    }

    public function setRecord($route)
    {
        $reflection = new ReflectionMethod($this, $this->action);
        if (count($reflection->getParameters()) > 0) {
            return; // tem parametros -> achar record no controller
        }
        if ($this->scope) {
            $parameters = $route->parameters();
            if (count($parameters) > 0) {
                $slug = end($parameters);
                $query = clone $this->scope;
                $this->record = $query->findOrFail($slug);
            }
        }
    }
    
    /*
    Find where to put this code:
    if ($method == 'show' && !$this->record) {
        throw new Exception('Show action without record. You need to set $this->record inside your controller.');
    }
    */

    protected function isRecordAction()
    {
        return in_array($this->action, $this->recordActions);
    }
}
