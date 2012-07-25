<?php
/**
 * DokuWiki Plugin stratainline (Reference Entry Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

if (!defined('DOKU_INC')) die('Meh.');

/**
 * Inline data entry for reference links. This shorthand can be used only
 * for the 'ref' type, and only handles internal links (both local and to
 * other pages).
 */
class syntax_plugin_stratainline_refentry extends DokuWiki_Syntax_Plugin {
    public function __construct() {
        $this->helper =& plugin_load('helper', 'stratabasic');
        $this->types =& plugin_load('helper', 'stratastorage_types');
        $this->triples =& plugin_load('helper', 'stratastorage_triples', false);
        $this->triples->initialize();
    }

    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'normal';
    }

    public function getSort() {
        return 285;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\[\['.STRATABASIC_PREDICATE.'?~(?:(?:[^[\]]*?\[.*?\])|.*?)\]\]',$mode,'plugin_stratainline_refentry');
    }

    public function handle($match, $state, $pos, &$handler){
        // match full pattern
        preg_match('/\[\[('.STRATABASIC_PREDICATE.'?)~((?:[^[\]]*?\[.*?\])|.*?)\]\]/msS', $match, $captures);

        // split into predicate and link
        $predicate = trim($captures[1]);
        $link = $captures[2];

        // handle link like dokuwiki (there is no usable function for this,
        // so we borrowed some code -- unfortunately)
        // Split title from URL
        $link = explode('|',$link,2);
        if ( !isset($link[1]) ) {
            $link[1] = NULL;
        } else if ( preg_match('/^\{\{[^\}]+\}\}$/',$link[1]) ) {
            // If the title is an image, convert it to an array containing the image details
            $link[1] = Doku_Handler_Parse_Media($link[1]);
        }
        $link[0] = trim($link[0]);

        // normalize the link
        $type = $this->types->loadType('ref');
        $link[0] = $type->normalize($link[0],null);

        // store and return
        return array(
            'predicate'=>$predicate,
            'link'=>$link[0],
            'title'=>$link[1]
        );
    }

    public function render($mode, &$R, $data) {
        global $ID;

        if($mode == 'xhtml' || $mode=='metadata') {
            // determine link title (if we have none from syntax)
            $heading = $data['title'];
            if($heading == null) {
                $titles = $this->triples->fetchTriples($data['link'], $this->triples->getTitleKey());
                if($titles) {
                    $heading = $titles[0]['object'];
                }
            }

            // we can not use the ref type's render method
            // (it uses its own internal heading determination)
            if($mode == 'xhtml') $R->doc .= '<span class="strata_field"><span class="strata_value stratatype_ref">';
            $R->internallink(':'.$data['link'],$heading);
            if($mode == 'xhtml') $R->doc .= '</span></span>';

            // Add triple to store if we render metadata
            if($mode == 'metadata' && (!isset($R->info['data']) || $R->info['data']==true)) {
                $predicate = $this->helper->normalizePredicate($data['predicate']);
                $this->triples->addTriple($ID, $predicate, $data['link'], $ID);
            }
            return true;
        }

        return false;
    }
}
