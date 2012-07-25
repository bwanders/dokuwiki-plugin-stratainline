<?php
/**
 * Strata Basic, data entry plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
 
if(!defined('DOKU_INC')) die('Meh.');
 
/**
 * Data entry syntax for dedicated data blocks.
 */
class syntax_plugin_stratainline_entry extends syntax_plugin_stratabasic_entry {
    function getPType() {
        return 'normal';
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\[\(.+?[:~].+?\)\]',$mode, 'plugin_stratainline_entry');
    }

    function preprocess($match, &$result) {
        preg_match('/^\[\((('.STRATABASIC_PREDICATE.'?)(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?(\*)?)\s*[~:](.*)\)\]$/',$match,$captures);

        return "<inlineentry>\n".$captures[1].' : '.$captures[6]."\n</inlineentry>";
    }

    function handleHeader($header, &$result) {
        // generate an empty header
        return '';
    }

    function handleFooter($footer, &$result) {
        // generate an empty footer
        return '';
    }


    function render($mode, &$R, $data) {
        global $ID;

        // pass problems or non-xhtml renders over to the parent
        if($data == array() || array_key_exists('error', $data) || $mode != 'xhtml') {
            return parent::render($mode, $R, $data);
        }

        // Display all the values as comma-separated list
        // (we render all keys, because it is easy)
        foreach($data['data'] as $key=>$values) {
            // render row content
            $R->doc .= '<span class="strata_field">';
            for($i=0;$i<count($values);$i++) {
                $triple =& $values[$i];
                if($i!=0) $R->doc .= ', ';
                $type = $this->types->loadType($triple['type']);
                $R->doc .= '<span class="strata_value stratatype_'.$triple['type'].'">';
                $type->render($mode, $R, $this->triples, $triple['value'], $triple['hint']);
                $R->doc .= '</span>';
            }
            $R->doc .= '</span>';
        }

        return true;
    }
}
