<?php

class BaseController extends Controller {

    /**
     * The name of the module
     * @var string
     */
    protected $module = '';

    /**
     * The name of the controller (without class path)
     * @var string
     */
    protected $controller = '';

    /**
     * The name of the model (without class path)
     * @var string
     */
    protected $model = '';

    /**
     * The name of the model (with class path)
     * @var string
     */
    protected $modelFullName = '';

    /**
     * The name of the form template (for CRUD auto handling)
     * @var string
     */
    protected $formTemplate = '';

	/**
	 * Constructor call
	 */
	public function __construct()
	{
        /*
         * Save module and controller name
         */
        $className          = get_class($this);
        $this->module       = explode('\\', $className)[2];
        $className          = class_basename($className);
        $this->controller   = str_replace(['Admin', 'Controller'], '', $className);

        /*
         * Enable auto CSRF protection
         */ 
        $this->beforeFilter('csrf', array('on' => 'post'));
	}

	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	protected function setupLayout()
	{
		if ( ! is_null($this->layout))
		{
			$this->layout = View::make($this->layout);
		}
	}

	/**
	 * Shortcut for $this->layout->nest(): Adds a view to the main layout.
	 *
	 * @param string $template
	 * @param array $data
	 * @return void
	 */
	protected function pageView($template = '', $data = array())
	{
		if ($this->layout != NULL) {
			$this->layout->page = View::make($template, $data);
		} else {
			throw new Exception('Error: $this->layout is NULL!');
		}
	}

	/**
	 * Shortcut for $this->layout->nest(): Adds a string to the main layout.
	 *
	 * @param string $output HTML or text to output on the template.
	 * @return void
	 */
	protected function pageOutput($output)
	{
		if ($this->layout != NULL) {
			$this->layout->page = $output;
		} else {
			throw new Exception('Error: $this->layout is NULL!');
		}
	}

	/**
	 * Adds a message view to the main layout.
	 *
	 * @param string $title
	 * @param string $text
	 * @return void
	 */
	protected function message($title, $text = '')
	{
		if ($this->layout != NULL) {
			$this->layout->page = View::make('message', array('title' => $title, 'text' => $text));
		} else {
			throw new Exception('Error: $this->layout is NULL!');
		}
	}

	/**
	 * Inserts a flash message to the main layout.
	 *
	 * @param string $title
	 * @return void
	 */
	protected function messageFlash($title)
	{
		Session::flash('_message', $title);
	}

    /**
     * Builds an index form (page) from a model and $data
     * 
     * @param  array $data Array with information how to build the form. See $defaults for details.
     * @return void
     */
    protected function buildIndexForm($data, $face = 'admin')
    {
        /*
         * Set default values
         */
        $defaults = array(
            'buttons'       => ['new'],
            'search'        => '',
            'searchFor'     => 'title',
            'tableHead'     => [],
            'tableRow'      => [],
            'actions'       => ['edit', 'delete'],
            'brightenFirst' => true,
            'order'         => 'id',
            'orderType'     => 'desc',
            );

        $data = array_merge($defaults, $data);

        /*
         * Generate Buttons
         */
        $buttons = '';
        if (is_array($data['buttons'])) {
            foreach ($data['buttons'] as $type) {
                $type = strtolower($type);
                switch ($type) {
                    case 'new':
                        $buttons .= button(t('Create new'), route($face.'.'.strtolower($this->controller).'.create'), 'add');
                        break;
                    case 'category':
                        $buttons .= button(t('Categories'), route($face.'.'.strtolower($this->module).'cats.index'), 'folder');
                        break;
                }
            }
        }

        /*
         * Get search string.
         */
        if (Input::old('search')) {
            $data['search'] = Input::old('search');
        }
        if (Input::get('search')) {
            $data['search'] = Input::get('search');
        }

        /*
         * Get order attributes.
         */
        if (Input::get('order')) {
            $order = strtolower(Input::get('order'));
            if (in_array($order, $data['tableHead'])) $data['order'] = $order;

            $orderType = strtolower(Input::get('ordertype'));
            if ($orderType === 'desc' or $orderType === 'asc') {
                $data['orderType'] = $orderType;
            }
        }
        $orderSwitcher = order_switcher($data['order'], $data['orderType'], $data['search']);

        /*
         * Retrieve model and entity from DB
         */
        $model = $this->modelFullName;
        $perPage = Config::get('app.backendItemsPerPage');
        if ($data['search'] and $data['searchFor']) {
            $entities = $model::orderBy($data['order'], $data['orderType'])
            ->where($data['searchFor'], 'LIKE', '%'.$data['search'].'%')
            ->paginate($perPage);
        } else {
            $entities = $model::orderBy($data['order'], $data['orderType'])
            ->paginate($perPage);   
        }

        $paginator = $entities->appends(['order' => $data['order'], 'orderType' => $data['orderType'], 'search' => $data['search']])->links();

        /*
         * Prepare the table (head and rows)
         */
        $tableHead = array();
        foreach ($data['tableHead'] as $title => $order) {
            if ($order != NULL) {
                $tableHead[] = HTML::link(URL::current().'?order='.$order, $title);
            } else {
                $tableHead[] = $title;
            }
        }
        if (sizeof($data['actions']) > 0) {
            $tableHead[] = t('Actions');
        }

        $tableRows = array();
        foreach ($entities as $entity) {
            $row = $data['tableRow']($entity);

            if (is_array($data['actions']) and sizeof($data['actions']) > 0) {
                $actionsCode = '';
                foreach ($data['actions'] as $action) {
                    if (is_string($action)) {
                        $action = strtolower($action);
                        switch ($action) {
                            case 'edit':
                                $actionsCode .= image_link('page_edit', 
                                    t('Edit'), 
                                    route($face.'.'.strtolower($this->controller).'.edit', [$entity->id]));
                                break;
                            case 'delete':
                                $actionsCode .= image_link('bin', 
                                    t('Delete'), 
                                    route($face.'.'.strtolower($this->controller).'.destroy', [$entity->id]).'?method=DELETE',
                                    false,
                                    ['data-confirm-delete' => true]);
                                break;
                        }
                        $actionsCode .= ' ';
                    }
                }
                $row[] = $actionsCode;
            }
            if (is_callable($data['actions'])) {
                $row[] = $data['actions']($entity);
            }

            $tableRows[] = $row;
        }

        /*
         * Generate the table
         */
        $contentTable = $this->contentTable($tableHead, $tableRows, $data['brightenFirst']);

        /*
         * Generate the view
         */
        $this->pageView('backend.index_form', array(
            'buttons'       => $buttons,
            'contentTable'  => $contentTable,
            'orderSwitcher' => $orderSwitcher,
            'paginator'     => $paginator,
            'searchString'  => $data['search']
            ));
    }

    /**
     * Returns HTML code for a table.
     * 
     * @param array     $header          Array with the table header items (String-Array)
     * @param array     $rows            Array with all the table rows items (Array containing String-Arrays)
     * @param bool      $highlightFirst  Enable special look for the items in the first column? (true/false)
     * @return string
     */
    protected function contentTable($header, $rows, $brightenFirst = true)
    {
        $code = '<table class="content-table">';

        /*
         * Table head
         */
        $code .= '<tr>';
        foreach ($header as $value) {
            $code .= '<th>';
            $code .= $value;
            $code .= '</th>';
        }
        $code .= '</tr>';

        /*
         * Table body
         */
        foreach ($rows as $row) {
            $code   .= '<tr>';
            $isFirst = true;
            foreach ($row as $value) {
                if ($isFirst and $brightenFirst) {
                    $code   .= '<td style="color: silver">';
                    $isFirst = false;
                } else {
                    $code .= '<td>';
                }
                $code .= $value;
                $code .= '</td>';
            }
            $code .= '</tr>';
        }

        $code .= '</table>';

        return $code;
    }
}