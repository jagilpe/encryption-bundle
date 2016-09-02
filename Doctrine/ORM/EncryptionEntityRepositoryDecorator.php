<?php

namespace EHEncryptionBundle\Doctrine\ORM;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use EHEncryptionBundle\Service\EncryptionService;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

class EncryptionEntityRepositoryDecorator implements ObjectRepository, Selectable
{
    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    private $wrapped;

    /**
     * @var \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    private $classMetadata;

    /**
     * @var \EHEncryptionBundle\Service\EncryptionService
     */
    private $encryptionService;

    public function __construct(EntityRepository $wrapped, ClassMetadata $classMetadata, EncryptionService $encryptionService)
    {
        $this->wrapped = $wrapped;
        $this->classMetadata = $classMetadata;
        $this->encryptionService = $encryptionService;
    }

    /**
     * Adds support for other methods
     *
     * @param string $method
     * @param array  $arguments
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array(array($this->wrapped, $method), $arguments);
    }

    /**
    * {@inheritDoc}
    * @see \Doctrine\Common\Persistence\ObjectRepository::find()
    */
    public function find($id)
    {
        return $this->wrapped->find($id);
    }

    /**
    * {@inheritDoc}
    * @see \Doctrine\Common\Persistence\ObjectRepository::findAll()
    */
    public function findAll()
    {
        return $this->wrapped->findAll();
    }

    /**
    * {@inheritDoc}
    * @see \Doctrine\Common\Persistence\ObjectRepository::findBy()
    */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        // Check if we have to filter or sort by an encrypted field
        if ($this->filterHasEncryptedField($criteria) || $this->orderByHasEncryptedField($orderBy)) {
            return $this->filterAndSortByEncryptedField($criteria, $orderBy, $limit, $offset);
        }
        else {
            // We can use the usual repository logic
            return $this->wrapped->findBy($criteria, $orderBy, $limit, $offset);
        }
    }

    /**
    * {@inheritDoc}
    * @see \Doctrine\Common\Persistence\ObjectRepository::findOneBy()
    */
    public function findOneBy(array $criteria)
    {
        return $this->findOneBy($criteria);
    }

    /**
    * {@inheritDoc}
    * @see \Doctrine\Common\Persistence\ObjectRepository::getClassName()
    */
    public function getClassName()
    {
        return $this->wrapped->getClassName();
    }

    /**
    * {@inheritDoc}
    * @see \Doctrine\Common\Collections\Selectable::matching()
    */
    public function matching(Criteria $criteria)
    {
        return $this->wrapped->matching($criteria);
    }

    /**
     * Checks if any of the fields in the filter criteria is encrypted
     *
     * @param array $criteria
     *
     * @return boolean
     */
    private function filterHasEncryptedField(array $criteria)
    {
        $result = false;

        if (!empty($criteria)) {
            $encryptedFields = array_keys($this->getEncryptedFields());
            $criteriaFields = array_keys($criteria);
            $commonFields = array_intersect($encryptedFields, $criteriaFields);

            $result = !empty($commonFields);
        }

        return $result;
    }

    /**
     * Checks if any of the fields in the order by clause is encrypted
     *
     * @param array $orderBy
     *
     * @return boolean
     */
    private function orderByHasEncryptedField(array $orderBy = null)
    {
        $result = false;

        if ($orderBy && !empty($orderBy)) {
            $encryptedFields = array_keys($this->getEncryptedFields());
            $orderByFields = array_keys($orderBy);
            $commonFields = array_intersect($encryptedFields, $orderByFields);

            $result = !empty($commonFields);
        }

        return $result;
    }

    /**
     * Filters and/or sorts using at least one encrypted field
     *
     * @param array $criteria
     * @param array $orderBy
     *
     * @return array
     */
    private function filterAndSortByEncryptedField(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $encryptedSort = $this->orderByHasEncryptedField($orderBy);

        $encryptedFields = $this->getEncryptedFields();
        $notEncryptedCriteria = array_diff_key($criteria, $encryptedFields);
        $notEncryptedOrderBy = !$encryptedSort ? $orderBy : null;

        // Get the result of the filter by not encrypted fields
        $result = $this->wrapped->findBy($notEncryptedCriteria, $notEncryptedOrderBy);

        $encryptedCriteria = array_intersect_key($criteria, $encryptedFields);
        if (!empty($result) && !empty($encryptedCriteria)) {
            $result = $this->filterWithEncryptedCriteria($result, $encryptedCriteria);
        }

        if ($encryptedSort && !empty($orderBy)) {
            $result = $this->sortWithEncryptedOrderBy($result, $orderBy);
        }

        if ($limit) {
            $offset = $offset ? $offset : 0;

            $result = array_slice($result, $offset, $limit);
        }

        return $result;
    }

    /**
     * Returns the fields of the entity that have the encryption enabled
     *
     * @return array
     */
    private function getEncryptedFields()
    {
        return $this->encryptionService->getEncryptionEnabledFields($this->classMetadata->getReflectionClass());
    }

    /**
     * Filters the values using by one or more encrypted fields
     *
     * @param array $values
     * @param array $orderBy
     *
     * @return array
     */
    private function filterWithEncryptedCriteria($values, array $encryptedCriteria)
    {
        $result = array_filter($values, function($value) use ($encryptedCriteria) {
            foreach ($encryptedCriteria as $fieldName => $fieldValue) {
                if (!$this->fieldValueMatches($value, $fieldName, $fieldValue)) {
                    return false;
                }
                return true;
            }
        });

        return $result;
    }

    /**
     * Check if the value of the field matches the given value
     *
     * @param mixed $entity
     * @param string $fieldName
     *
     * @return mixed
     */
    private function fieldValueMatches($entity, $fieldName, $fieldValue)
    {
        $value = $this->getFieldValue($entity, $fieldName);
        if (is_array($fieldValue)) {
            $matches = in_array($value, $fieldValue);
        }
        else {
            $matches = ($value === $fieldValue);
        }

        return $matches;
    }

    /**
     * Sorts the values using by one or more encrypted fields
     *
     * @param array $values
     * @param array $orderBy
     *
     * @return array
     */
    private function sortWithEncryptedOrderBy($values, array $orderBy)
    {
        usort($values, function($entity1, $entity2) use ($orderBy) {
            foreach ($orderBy as $fieldName => $order) {
                $value1 = $this->getFieldValue($entity1, $fieldName);
                $value2 = $this->getFieldValue($entity2, $fieldName);

                if ($value1 < $value2) {
                    $result = (strtoupper($order) === 'ASC') ? -1 : 1;
                }
                elseif ($value1 > $value2) {
                    $result = (strtoupper($order) === 'ASC') ? 1 : -1;
                }
                return $result;
            }

            // If we are here is because all the values were equal
            return 0;
        });

        return $values;
    }

    /**
     * Returns the value of a field for a given entity
     *
     * @param mixed $entity
     * @param string $fieldName
     *
     * @return mixed
     */
    private function getFieldValue($entity, $fieldName)
    {
        $getter = 'get'.ucfirst($fieldName);
        return $entity->{$getter}();
    }
}