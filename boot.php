<?php

if (rex::isBackend()) {
    
    if (rex_plugin::exists('ui_tools','jquery-minicolors')) {
        rex_view::addJSFile($this->getAssetsUrl('jquery.tablesorter.min.js'));
    }
    rex_view::addCssFile($this->getAssetsUrl('theme.default.min.css'));    
    
    rex_extension::register('PAGE_STRUCTURE_HEADER', function ($params) {
        $for_categories = explode(',',$this->getConfig('for_categories'));
        $params = $params->getParams();
        
        // nur in den eingestellten Kategorien ausführen
        if (
                (!$this->getConfig('for_all_categories') && in_array($params['category_id'],$for_categories)) ||
                ($this->getConfig('for_all_categories') && !in_array($params['category_id'],$for_categories))
            ) {
            
            rex_extension::register('OUTPUT_FILTER', function ($params) {
                
                // Tabelle und Pager laden
                list($table2,$pager) = structure_plus::get_article_table();
                
                // Ersetzungen durchführen
                $subject = $params->getSubject();
                $subject = preg_replace('/(<table.*?<\/table>.*?)<table.*?<\/table>/s','$1###tab2###',$subject,1);
                $subject = str_replace('###tab2###',$table2,$subject);
                rex_logger::factory()->log('notice',$pager,[],__FILE__,__LINE__);

                $subject = preg_replace('/<nav class="rex-nav-pagination">.*?<\/nav>/s',$pager,$subject);
                $subject .= '<script>                        
                        $(document).on(\'rex:ready\', function (e, container) {
                            $("#sp_table").tablesorter();
                        });
                        </script>';
                
                return $subject;
            });
        }        
    });
    
    // Setzt automatisch das art_online_from auf das aktuelle Datum
    $extension_points = ['ART_ADDED','CAT_ADDED'];
    foreach ($extension_points as $extensionpint) {
        rex_extension::register($extensionpint, function ($params) {
            $_params = $params->getParams();
            $sql = rex_sql::factory();
            $sql->setTable(rex::getTable('article'));
            $sql->select();
            $fields = $sql->getFieldnames();
            if (in_array('art_online_from',$fields)) {
                $sql->setTable(rex::getTable('article'));
                $sql->setValue('art_online_from',time());
                $sql->setWhere('id = :id',['id'=>$_params['id']]);
                $sql->update();
            }
        });
    }
    
}