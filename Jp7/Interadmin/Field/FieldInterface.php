<?php

namespace Jp7\Interadmin\Field;

use ADOFetchObj;
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
    public function getCellTag(ADOFetchObj $record);

    /**
     * Return inner HTML for <th> tag
     *
     * @return Element|string
     */
    public function getHeaderHtml();

    /**
     * Return inner HTML for <td> tag
     *
     * @param ADOFetchObj $record
     * @return Element|string
     */
    public function getCellHtml(ADOFetchObj $record);

    /**
     * Return inner text for header
     *
     * @return string
     */
    public function getHeaderText();

    /**
     * Return inner text for cell
     *
     * @return string
     */
    public function getCellText(ADOFetchObj $record);
}
