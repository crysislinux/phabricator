<?php

final class GugudManiphestCustomField
  extends ManiphestCustomField
  implements PhabricatorStandardCustomFieldInterface {

  private $fieldValue;

  public function getFieldValue() {
    return $this->fieldValue;
  }

  public function setFieldValue($value) {
    $this->fieldValue = $value;
    return $this;
  }

  public function config() {
    return array(
      'gugud:deadline' => array(
        'name' => pht('Deadline'),
        'type' => 'int',
        'required' => true,
      ),
      'gugud:workload' => array(
        'name' => pht('Workload'),
        'type' => 'int',
        'placeholder' => pht('Test Placeholder'),
        'required' => true,
      ),
      'gugud:category' => array(
        'name' => pht('Category'),
        'type' => 'select',
        'options' => array(
          'ca1' => pht('Ca 1'),
          'ca2' => pht('Ca 2')
        ),
        'default' => 'ca1',
        'required' => true,
      ),
    );
  }

  public function renderDeadline($value) {
    return  pht('%s Day(s)', new PhutilNumber($value));
  }

  public function renderPropertyViewValue(array $handles) {
    $value = $this->getFieldValue();
    if (!strlen($value)) {
      return null;
    }

    if ($this->getProxy()->getFieldKey() === 'std:maniphest:gugud:deadline') {
      return $this->renderDeadline($value);
    }

    return  $value;
  }

  public function setValueFromStorage($value) {
    parent::setValueFromStorage($value);
    return $this->setFieldValue($value);
  }

  public function getStandardCustomFieldNamespace() {
    return 'maniphest';
  }

  public function createFields($object) {
    $config = $this->config();
    $fields = PhabricatorStandardCustomField::buildStandardFields(
      $this,
      $config);

    return $fields;
  }
}
