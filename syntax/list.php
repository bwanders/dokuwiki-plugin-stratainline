<?php
/**
 * Strata Inline, inline list plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
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


    function preprocess($match, &$result, &$typemap) {
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
        if($data == array()) {
            return;
        }

        // execute the query
        $result = $this->triples->queryRelations($data['query']);

        if($result == false) {
            return;
        }

        // prepare all 'columns'
        $fields = array();
        foreach($data['fields'] as $field=>$meta) {
            $fields[] = array(
                'name'=>$field,
                'type'=>$this->types->loadType($meta['type']),
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
                    $values = $f['aggregate']->aggregate($row[$f['name']],$f['aggregateHint']);
                    if(!count($values)) continue;
                    if($fieldCount>1) $R->doc .= '; ';
                    if($fieldCount==1) $R->doc .= ' (';
                    $firstValue = true;
                    foreach($values as $value) {
                        if(!$firstValue) $R->doc .= ', ';
                        $f['type']->render($mode, $R, $this->triples, $value, $f['hint']);
                        $firstValue = false;
                    }
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
