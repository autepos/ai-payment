<?php
namespace Autepos\AiPayment\Tests\ContractTests;
trait ContractTestBase{

    /**
     * Get the instance of the implementation of the subject contract.
     * 
     * @param string $subjectContract The fully qualified name of the contract to be tested.
     *
     * @return mixed An instance of the subject contract implementation.
     * @throws \Exception If subjectInstance() is not defined in the user test or if wrong instance is returned.
     */
    private function subjectInstanceOrFail(string $subjectContract)
    {
        if (method_exists($this, 'subjectInstance')) {
            $instance = $this->subjectInstance();
            if (is_a($instance,$subjectContract)) {
                return $instance;
            }
        }
        throw new \Exception('subjectInstance() is missing or is returning a wrong value. Tips: Override the getInstance() method in your test. You should then return an instance of '. $subjectContract .' under test from the subjectInstance()');
    }
}