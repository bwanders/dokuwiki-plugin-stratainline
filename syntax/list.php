<?php
/**
 * Strata Inline, inline list plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
 
if(!defined('DOKU_INC')) die('Meh.');
 
/**
 * List syntax for basic query handling.
 */
class syntax_plugin_stratainline_list extends syntax_plugin_stratabasic_select {
    function __construct() {
        parent::__construct();
   }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{list>'.$this->helper->fieldsShortPattern(false).'* *(?:"[^"]*")? *\|.+?\}\}',$mode, 'plugin_stratainline_list');
    }

    function getPType() {
        return 'normal';
    }

    function getSort() {
        return 310;
    }


    function preprocess($match, &$handler, &$result, &$typemap) {
        preg_match('/\{\{list>('.$this->helper->fieldsShortPattern(false).'*) *(?:(")([^"]*)")? *\|(.+?)\}\}/s',$match,$captures);
        list(,$header,$separatorIndicator, $separator, $rest) = $captures;
        $footer = '';

        $rest = str_replace(array(';','{','}'), array("\n", "{\n", "\n}\n"), $rest);

        if($separatorIndicator) {
            $result['separator'] = $separator;
        } else {
            $result['separator'] = ', ';
        }

        return $header."\n".$rest."\n".$footer;
    }

    function render($mode, &$R, $data) {
        if($data == array() || isset($data['error'])) {
            if($mode == 'xhtml') {
                $R->emphasis_open();
                $R->doc .= '['.$R->_xmlEntities($this->helper->getLang('content_error_explanation'));
                $R->doc .= ': '.$data['error']['message'];
                $R->doc .= ']';
                $R->emphasis_close();
            }
            return;
        }

        $query = $this->prepareQuery($data['query']);

        // execute the query
        $result = $this->triples->queryRelations($query);

        if($result == false) {
            if($mode == 'xhtml') {
                $R->emphasis_open();
                $R->doc .= '['.$R->_xmlEntities($this->helper->getLang('content_error_explanation')).']';
                $R->emphasis_close();
            }
            return;
        }

        // prepare all 'columns'
        $fields = array();
        foreach($data['fields'] as $meta) {
            $fields[] = array(
                'variable'=>$meta['variable'],
                'type'=>$this->types->loadType($meta['type']),
                'typeName'=>$meta['type'],
                'hint'=>$meta['hint'],
                'aggregate'=>$this->types->loadAggregate($meta['aggregate']),
                'aggregateHint'=>$meta['aggregateHint']
            );
        }

        $first = true;

        if($mode == 'xhtml' || $mode == 'metadata') {
            // render each result
            foreach($result as $row) {
                if(!$first) {
                    $R->doc .= hsc($data['separator']);
                }

                $fieldCount = 0;

                foreach($fields as $f) {
                    $values = $f['aggregate']->aggregate($row[$f['variable']],$f['aggregateHint']);
                    if(!count($values)) continue;
                    if($fieldCount>1) $R->doc .= '; ';
                    if($fieldCount==1) $R->doc .= ' (';
                    $firstValue = true;
                    $R->doc .= '<span class="strata_field">';
                    foreach($values as $value) {
                        if(!$firstValue) $R->doc .= ', ';
                        $R->doc .= '<span class="strata_value stratatype_'.$f['typeName'].'">';
                        $f['type']->render($mode, $R, $this->triples, $value, $f['hint']);
                        $R->doc .= '</span>';
                        $firstValue = false;
                    }
                    $R->doc .= '</span>';
                    $fieldCount++;
                }

                if($fieldCount>1) $R->doc .= ')';

                $first = false;
            }
            $result->closeCursor();

            return true;
        } 

        return false;
    }
}
