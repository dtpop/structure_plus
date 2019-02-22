<?php

if (rex::isBackend()) {
    
    rex_view::addJSFile($this->getAssetsUrl('jquery.tablesorter.min.js'));
    rex_view::addCssFile($this->getAssetsUrl('theme.default.min.css'));    
    
    rex_extension::register('PAGE_STRUCTURE_HEADER', function ($params) {
        $for_categories = explode(',',rex_config::get('structure_plus','for_categories'));
        $params = $params->getParams();
        
        // nur in den eingestellten Kategorien ausführen
        if (in_array($params['category_id'],$for_categories)) {
            
            rex_extension::register('OUTPUT_FILTER', function ($params) {
                
                // Tabelle und Pager laden
                list($table2,$pager) = structure_plus::get_article_table();
                
                // Ersetzungen durchführen
                $subject = $params->getSubject();
                $subject = preg_replace('/(<table.*?<\/table>.*?)<table.*?<\/table>/s','$1###tab2###',$subject,1);
                $subject = str_replace('###tab2###',$table2,$subject);
                
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
}