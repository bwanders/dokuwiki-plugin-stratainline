<?php
/**
 * DokuWiki Plugin stratainline (Format-only Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

if (!defined('DOKU_INC')) die('Meh.');

class syntax_plugin_stratainline_format extends DokuWiki_Syntax_Plugin {
    public function __construct() {
        $this->types =& plugin_load('helper', 'stratastorage_types');
        $this->triples =& plugin_load('helper', 'stratastorage_triples');
    }

    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'normal';
    }

    public function getSort() {
        return 320;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\(.*?[~:].*?\)\}',$mode,'plugin_stratainline_format');
    }

    public function handle($match, $state, $pos, &$handler){
        $result = array(
            'type'=>'text',
            'hint'=> null,
            'values'=>array()
        );

        // match full pattern
        preg_match('/\{\(([a-z0-9]+)(?:\(([^)]+)\))?(?:@([a-z0-9]*)(?:\(([^\)]*)\))?)?(\*)?\s*[~:](.*)\)\}/',$match,$parts);

        // assign useful names
        list($match, $type, $hint, $agg, $aggHint, $multi, $values) = $parts;

        $result['type'] = $type;
        $result['hint'] = $hint;

        $type = $this->types->loadType($type);

        // determine values, splitting on commas if necessary
        if($multi == '*') {
            $values = explode(',',$values);
        } else {
            $values = array($values);
        }

        // normalize values
        foreach($values as $v) {
            $v = utf8_trim($v);
            if($v == '') continue;

            // replace the [[]] quasi-magic token with the empty string
            if($v == '[[]]') $v = '';

            $result['values'][] = $type->normalize($v, $hint);
        }

        if($agg != '') {
            $agg = $this->types->loadAggregate($agg);
            $result['values'] = $agg->aggregate($result['values'], $aggHint);
        }

        // store and return
        return $result;
    }

    public function render($mode, &$R, $data) {
        global $ID;

        if($mode == 'xhtml' || $mode=='metadata') {
            $type = $this->types->loadType($data['type']);

            if($mode == 'xhtml') $R->doc .= '<span class="strata_field">';
            for($i=0;$i<count($data['values']);$i++) {
                $v = $data['values'][$i];
                if($i!=0) $R->doc .= ', ';
                if($mode == 'xhtml') $R->doc .= '<span class="strata_value stratatype_'.$data['type'].'">';
                $type->render($mode, $R, $this->triples, $v, $data['hint']);
                if($mode == 'xhtml') $R->doc .= '</span>';
            }
            if($mode == 'xhtml') $R->doc .= '</span>';

            return true;
        }

        return false;
    }
}
