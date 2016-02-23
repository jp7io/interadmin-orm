<?php

class Jp7_InterAdmin_Soap_Generic
{
    /**
     * Retorna todos os registros publicados.
     *
     * @param string $className
     * @param array  $options
     *
     * @return mixed
     */
    public function get($className, $options = [])
    {
        $tipo = Jp7_InterAdmin_Soap::getClassTipo($className);

        return $tipo->find($options);
    }

    /**
     * Returna o primeiro registro.
     *
     * @param string $className
     * @param array  $options
     *
     * @return mixed
     */
    public function getFirst($className, $options = [])
    {
        $options['limit'] = 1;

        return reset($this->get($className, $options));
    }

    /**
     * Returna todos os registros, incluindo os deletados e os não publicados.
     *
     * @param string $className
     * @param array  $options
     *
     * @return mixed
     */
    public function getAll($className, $options = [])
    {
        $options['use_published_filters'] = false;

        return $this->get($className, $options);
    }
}
