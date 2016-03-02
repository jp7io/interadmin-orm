<?php

namespace Jp7\Interadmin\Field;

use HtmlObject\Element;

///////// Hierarchy ////////////////
// Tag          <td>{!! $Html !!}</td>
// -> Html      <span>{{ $Text }}</span>
//    -> Text   Hi
// --------------------------------
// <td><span>Hi</span></td>
//
interface FieldInterface
{
    
    public function setRecord($record);
    
    /**
     * Returns object for <th> tag
     *
     * @return Element
     */
    public function getHeaderTag();

    /**
     * Returns object for <td> tag
     *
     * @return Element
     */
    public function getCellTag();

    /**
     * Return inner HTML for <th> tag
     *
     * @return Element|string
     */
    public function getHeaderHtml();

    /**
     * Return inner HTML for <td> tag
     *
     * @return Element|string
     */
    public function getCellHtml();

    /**
     * Return inner text for header
     *
     * @return string
     */
    public function getLabel();

    /**
     * Return inner text for cell
     *
     * @return string
     */
    public function getText();
    
    /**
     * Return object for <div class="form-group">...</div>
     *
     * @return Element
     */
    public function getEditTag();
    
    /**
     * @return array
     */
    public function getRules();
}
