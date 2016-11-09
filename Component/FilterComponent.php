<?php

/*
 * (c) XM Media Inc. <dhein@xmmedia.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XM\FilterBundle\Component;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provides a base for a service to help creating a filter form and record list.
 *
 * @author Darryl Hein, XM Media Inc. <dhein@xmmedia.com>
 */
abstract class FilterComponent
{
    /**
     * @var RequestStack
     */
    protected $request;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * The form object after it's been built.
     *
     * @var \Symfony\Component\Form\FormInterface
     */
    protected $form;

    /**
     * The form type class.
     *
     * @var string
     */
    protected $formType = FormType::class;

    /**
     * The block name from the form.
     * Used to generate the query string.
     *
     * @var string
     */
    protected $formBlockName = 'filter';

    /**
     * @var PaginatorInterface
     */
    protected $paginator;

    /**
     * Where the key for the session storage.
     *
     * @var string
     */
    protected $sessionKey;

    /**
     * The record count per page.
     *
     * @var integer
     */
    protected $pageLimit = 20;

    /**
     * @param RequestStack $requestStack The request stack
     * @param SessionInterface $session
     * @param FormFactoryInterface $formFactory
     * @param PaginatorInterface $paginator
     * @throws \Exception
     */
    public function __construct(
        RequestStack $requestStack,
        SessionInterface $session,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->session = $session;
        $this->formFactory = $formFactory;
        $this->paginator = $paginator;

        if (!$this->sessionKey) {
            throw new \Exception('The session key must be set');
        }
    }

    /**
     * Returns the default filters.
     *
     * @return array
     */
    abstract public function filterDefaults();

    /**
     * Returns the defaults for the session.
     *
     * @return array
     */
    public function sessionDefaults()
    {
        return [
            'filters' => [],
            'page' => null,
            'ids' => [],
        ];
    }

    /**
     * Sets the key in the session.
     * The filters are merged with the defaults as well as the data for
     * this "filter" in the session.
     */
    public function updateSession()
    {
        $existingSession = $this->getSession();
        if ($this->form) {
            $filters = $this->form->getData() + $this->filterDefaults();
        } else {
            $filters = $this->filterDefaults();
        }

        $currentValues = [
            'filters' => $filters,
            'page' => $this->pageFromQuery(),
        ];

        $newSession = array_merge($this->sessionDefaults(), $existingSession, $currentValues);

        $this->session->set($this->sessionKey, $newSession);
    }

    /**
     * Merges the passed array with the session data and updates the session.
     *
     * @param  array  $newValues The values in the session to replace.
     */
    public function mergeSession(array $newValues)
    {
        $existing = $this->getSession();

        $new = array_merge($existing, $newValues);

        $this->session->set($this->sessionKey, $new);
    }

    /**
     * Gets all or a single key from the session.
     *
     * @param  string $key The session key to retrieve or null for the entire session.
     *
     * @return mixed|null
     */
    public function getSession($key = null)
    {
        // merge the arrays, using the default values if not in the session
        $sessionData = (array) $this->session->get($this->sessionKey) + $this->sessionDefaults();

        if (null === $key) {
            return $sessionData;
        } else if (array_key_exists($key, $sessionData)) {
            return $sessionData[$key];
        } else {
            return null;
        }
    }

    /**
     * Returns the current page from the query.
     * If the page is not set, it will return page 1.
     *
     * @return int
     */
    public function pageFromQuery()
    {
        $page = $this->request->query->getInt('page');

        return ($page > 0) ? $page : 1;
    }

    /**
     * Returns the query string based on the filters and page in the session.
     *
     * @return string
     */
    public function query()
    {
        $filters = $this->getSession('filters');
        $page = $this->getSession('page');

        $queryData = [
            $this->formBlockName => $filters,
            'page'               => $page,
        ];

        return http_build_query($queryData);
    }

    /**
     * Creates the filter form and pulls the filter values from request.
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createForm()
    {
        $defaults = $this->filterDefaults();

        $this->form = $this->formFactory->create($this->formType, $defaults);
        $this->form->handleRequest($this->request);

        return $this->form;
    }

    /**
     * Stores the full list of record IDs in the session.
     *
     * @param  \Doctrine\ORM\QueryBuilder $idOnlyQb The query builder
     *
     * @return void
     */
    public function storeResult($idOnlyQb)
    {
        $result = $idOnlyQb->getQuery()->getArrayResult();

        $resultIds = array_column($result, 'id');

        $this->mergeSession(['ids' => $resultIds]);
    }

    /**
     * Returns the configured KNP Paginator instance.
     *
     * @param  \Doctrine\ORM\Query $query The query
     *
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getPagination($query)
    {
        $pagination = $this->paginator->paginate(
            $query,
            $this->pageFromQuery(),
            $this->getPageLimit()
        );

        return $pagination;
    }

    /**
     * Finds the previous and next IDs in the record list.
     * Returns an array with the first key as the previous
     * and second key as the next system ID.
     * Either key can be NULL if there is no previous or next.
     *
     * @param  int $currentRecordId The current record ID
     *
     * @return array
     */
    public function prevNext($currentRecordId)
    {
        $recordIds = (array) $this->getSession('ids');
        $prevRecordId = $nextRecordId = null;

        $currentKey = array_search($currentRecordId, $recordIds);
        if (false !== $currentKey) {
            if ($currentKey > 0) {
                $prevRecordId = $recordIds[$currentKey - 1];
            }
            if ($currentKey < count($recordIds) - 1) {
                $nextRecordId = $recordIds[$currentKey + 1];
            }
        }

        return [$prevRecordId, $nextRecordId];
    }

    /**
     * Returns the data from the form.
     *
     * @return array
     */
    public function getFormData()
    {
        return $this->form->getData();
    }

    /**
     * Returns the page limit count.
     *
     * @return int
     */
    public function getPageLimit()
    {
        return $this->pageLimit;
    }
}