<?
namespace Yolva\Base;
abstract class BaseDecorator
{
    protected $object = null;

    public function __construct($object)
    {
        $this->object = $object;
    }


    public function __call($methodName, array $arguments)
    {
        $this->beforeMethodCalled($methodName, $arguments);
        $result = call_user_func_array(array($this->object, $methodName), $arguments);
        $resultAfter = $this->afterMethodCalled($methodName, $arguments, $result);
        return $resultAfter;
    }


    protected abstract function beforeMethodCalled($methodName, array $arguments);

    protected abstract function afterMethodCalled($methodName, array $arguments, $result);
}
?>