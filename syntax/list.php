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
        $this->Lexer->addSpecialPattern('\{\{data>'.$this->helper->fieldsShortPattern().'* *(?:"[^"]*")? *\|.+?\}\}',$mode, 'plugin_stratainline_list');
    }

    function getPType() {
        return 'normal';
    }

    function getSort() {
        return 310;
    }


    function preprocess($match, &$result, &$typemap) {
        preg_match('/\{\{data>('.$this->helper->fieldsShortPattern().'*) *(?:(")([^"]*)")? *\|(.+?)\}\}/',$match,$captures);

        $header = $captures[1];
        $separatorIndicator = $captures[2];
        $separator = $captures[3];
        $rest = $captures[4];
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
                'hint'=>$meta['hint']
            );
        }

        $first = true;

        if($mode == 'xhtml') {
            // render each result
            foreach($result as $row) {
                if(!$first) {
                    $R->doc .= hsc($data['separator']);
                }

                $fieldCount = 0;

                foreach($fields as $f) {
                    if($row[$f['name']] != null) {
                        if($fieldCount>1) $R->doc .= ', ';
                        if($fieldCount==1) $R->doc .= ' (';
                        $f['type']->render($mode, $R, $this->triples, $row[$f['name']], $f['hint']);
                        $fieldCount++;
                    }
                }

                if($fieldCount>1) $R->doc .= ')';

                $first = false;
            }
            $result->closeCursor();

            return true;
        } elseif($mode == 'metadata') {
            // render all rows in metadata mode to enable things like backlinks
            foreach($result as $row) {
                foreach($fields as $f) {
                    if($row[$f['name']] != null) {
                        $f['type']->render($mode, $R, $this->triples, $row[$f['name']], $f['hint']);
                    }
                }
            }
            $result->closeCursor();

            return true;
        }


        return false;
    }
}