<?php

namespace Jp7;

use Symfony\Component\DomCrawler\Crawler;
use Date;
use InvalidArgumentException;

class FormInspector
{
    public function crawler($url, $filter)
    {
        $html = file_get_contents($url);

        return $this->crawlerFromHtml($html, $filter);
    }

    public function crawlerFromHtml($html, $filter)
    {
        $crawler = new Crawler($html);

        return $crawler->filter($filter);
    }

    public function info(Crawler $form)
    {
        $fields = [];

        $form->filter('input,select')->each(function ($input) use (&$fields) {
            if ($name = $input->attr('name')) {
                $fields[$name] = $this->fieldInfo($input);
            }
        });

        return $fields;
    }

    public function fieldInfo(Crawler $input)
    {
        $info = (object) [
            'value' => '',
            'type' => '',
        ];

        $info->tag = $input->getNode(0)->nodeName;
        if ($info->tag == 'select') {
            $select = $this->selectInfo($input);
            $info->options = $select->options;
            $info->value = $select->selected;
        } else {
            $info->type = $input->attr('type');
            $info->value = $input->attr('value');
        }

        return $info;
    }

    public function selectInfo(Crawler $input)
    {
        $return = (object) [
            'options' => [],
            'selected' => null,
        ];

        $input->filter('option')->each(function ($option, $i) use ($return) {
            if ($value = $option->attr('value')) {
                if ($option->attr('selected') || $i == 0) {
                    $return->selected = $value;
                }
                $return->options[$value] = $option->text();
            }
        });

        return $return;
    }

    public function compare($ar1, $ar2, $ignoreList = [])
    {
        foreach ($ar2 as $name => $data) {
            $ok = false;
            $ignore = isset($ignoreList[$name]) ? $ignoreList[$name] : '';

            if ($data->type == 'submit' || $ignore == 'isset') {
                $ok = true; // Submit nao precisa existir
            } elseif (array_key_exists($name, $ar1)) {
                $other = $ar1[$name];

                if ($data->type == 'checkbox') {
                    $ok = true; // ignora valor
                } elseif ($data->tag == 'select') {
                    $ok = $this->compareSelect($data, $other, $ignore);
                } elseif ($data->type == 'date' || $other->type == 'date') {
                    $ok = $this->compareDate($data, $other, $ignore);
                } else {
                    $ok = $this->compareOther($data, $other, $ignore);
                }
            }
            if ($ok) {
                unset($ar2[$name]);
            }
        }

        return $ar2;
    }

    private function compareDate($data, $other, $ignore)
    {
        if ($ignore == 'value' || $data->value == $other->value) {
            return true;
        } elseif ($this->normalizeDate($data->value) == $this->normalizeDate($other->value)) {
            return true;
        }

        return false;
    }

    private function compareSelect($data, $other, $ignore)
    {
        if ($ignore == 'value' || $data->value == $other->value) {
            if ($other->tag != 'select') {
                $other->options = [];
            }
            if ($ignore == 'options' || array_keys($data->options) == array_keys($other->options)) {
                return true;
            }
            $data->options_diff = array_diff_key($data->options, $other->options);
        }

        return false;
    }

    private function compareOther($data, $other, $ignore)
    {
        if ($ignore == 'value' || $data->value == $other->value) {
            return true;
        }

        return false;
    }

    private function normalizeDate($value)
    {
        try {
            return Date::createFromFormat('d/m/Y', $value);
        } catch (InvalidArgumentException $e) {
            // proximo
        }
        try {
            return Date::createFromFormat('Y-m-d', $value);
        } catch (InvalidArgumentException $e) {
            return $value;
        }
    }
}
