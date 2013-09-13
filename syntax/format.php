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
        $this->util =& plugin_load('helper', 'strata_util');
        $this->triples =& plugin_load('helper', 'strata_triples');
        $this->syntax =& plugin_load('helper', 'strata_syntax');
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

        $p = $this->syntax->getPatterns();

        // match full pattern
        preg_match("/\{\(({$p->type})?\s*({$p->aggregate})?\s*(\*)?\s*[:~]\s*({$p->any}?)\)\}$/",$match,$parts);

        // assign useful names
        list(, $ptype, $aggregate, $multi, $values) = $parts;

        // select type
        if($ptype != '') {
            list($type, $hint) = $p->type($ptype);
        } else {
            list($type, $hint) = $this->util->getDefaultType();
        }

        $result['type'] = $type;
        $result['hint'] = $hint;

        $type = $this->util->loadType($type);

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

        // aggregate values if requested
        if($aggregate != '') {
            list($agg, $aggHint) = $p->aggregate($aggregate);
            $agg = $this->util->loadAggregate($agg);
            $result['values'] = $agg->aggregate($result['values'], $aggHint);
        }

        // store and return
        return $result;
    }

    public function render($mode, &$R, $data) {
        global $ID;

        if($mode == 'xhtml' || $mode=='metadata') {
            $this->util->renderField($mode, $R, $this->triples, $data['values'], $data['type'], $data['hint']);
            return true;
        }

        return false;
    }
}
