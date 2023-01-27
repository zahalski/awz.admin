<?php

namespace Awz\Admin\Dict;

/**
 * Реализация параметров в классе
 */
abstract class Parameters {

    /**
     * @var array
     */
    protected $params = array();

    /**
     * @param array $params
     */
    public function __construct(array $params = array())
    {
        $this->setParameters($params);
    }

    /** Установка параметров
     * массив string=>mixed
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    protected function setParameters(array $params)
    {
        foreach($params as $code=>$value){
            $code = (string) $code;
            if($code)
                $this->setParameter($code, $value);
        }
        return $this;
    }

    /**
     * Set parameter from code
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setParameter(string $name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Get parameter from code
     *
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getParameter(string $name, $default=null){
        if(isset($this->params[$name]))
            return $this->params[$name];
        return $default;
    }

    /**
     * Get parameter from code
     * alias getParameter
     *
     * @param string $name
     * @param $default
     * @return mixed|null
     */
    public function getParam(string $name, $default=null)
    {
        return $this->getParameter($name, $default);
    }

    /**
     * Set parameter from code
     * alias setParameter
     *
     * @param string $name
     * @param $value
     * @return $this
     */
    public function setParam(string $name, $value)
    {
        return $this->setParameter($name, $value);
    }

}