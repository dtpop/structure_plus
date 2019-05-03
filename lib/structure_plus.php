<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of structure_plus
 *
 * @author wolfgang
 */
class structure_plus {

    public static function get_article_table() {
        
        // basic request vars
        $category_id = rex_request('category_id', 'int');
        $article_id = rex_request('article_id', 'int');
        $clang = rex_request('clang', 'int');
        $ctype = rex_request('ctype', 'int');

        // additional request vars
        $artstart = rex_request('artstart', 'int');
        $catstart = rex_request('catstart', 'int');
        $edit_id = rex_request('edit_id', 'int');
        $function = rex_request('function', 'string');

        $info = '';
        $warning = '';

        $category_id = rex_category::get($category_id) ? $category_id : 0;
        $article_id = rex_article::get($article_id) ? $article_id : 0;
        $clang = rex_clang::exists($clang) ? $clang : rex_clang::getStartId();
        
        $config = rex_config::get('structure_plus');
        if (!$config['additional_db_column']) {
            $config['additional_db_column'] = 'priority';
        }
        if ($config['items_per_page'] < 30) {
            $config['items_per_page'] = 0;
        }
        $show_additional_column = in_array($config['additional_db_column'],['name','priority']) ? false : true;
        
        /*
            "additional_column_label" => "Online vom ..."
            "additional_db_column" => "art_online_from"
            "field_type" => "date"
            "for_categories" => "23,25"
            "order_direction" => "ASC"
           "items_per_page" => "30"
         * 
         */
        
        // --------------------------------------------- Mountpoints

        $mountpoints = rex::getUser()->getComplexPerm('structure')->getMountpoints();
        if (count($mountpoints) == 1 && $category_id == 0) {
            // Nur ein Mointpoint -> Sprung in die Kategory
            $category_id = current($mountpoints);
        }

        // --------------------------------------------- Rechte prüfen
        $KATPERM = rex::getUser()->getComplexPerm('structure')->hasCategoryPerm($category_id);
        
        $stop = false;
        if (rex_clang::count() > 1) {
            if (!rex::getUser()->getComplexPerm('clang')->hasPerm($clang)) {
                $stop = true;
                foreach (rex_clang::getAllIds() as $key) {
                    if (rex::getUser()->getComplexPerm('clang')->hasPerm($key)) {
                        $clang = $key;
                        $stop = false;
                        break;
                    }
                }

                if ($stop) {
                    echo rex_view::error('You have no permission to this area');
                    exit;
                }
            }
        } else {
            $clang = rex_clang::getStartId();
        }

        $context = new rex_context([
            'page' => 'structure',
            'category_id' => $category_id,
            'article_id' => $article_id,
            'clang' => $clang,
        ]);
        
        $catStatusTypes = rex_category_service::statusTypes();
        $artStatusTypes = rex_article_service::statusTypes();        
        
//        dump($context); exit;

        $echo = '';

        if ($category_id > 0 || ($category_id == 0 && !rex::getUser()->getComplexPerm('structure')->hasMountpoints())) {
//            $withTemplates = $this->getPlugin('content')->isAvailable();
            $withTemplates = true;
            $tmpl_head = '';
            if ($withTemplates) {
                $template_select = new rex_select();
                $template_select->setName('template_id');
                $template_select->setSize(1);
                $template_select->setStyle('class="form-control selectpicker"');

                $templates = rex_template::getTemplatesForCategory($category_id);
                if (count($templates) > 0) {
                    foreach ($templates as $t_id => $t_name) {
                        $template_select->addOption(rex_i18n::translate($t_name, false), $t_id);
                        $TEMPLATE_NAME[$t_id] = rex_i18n::translate($t_name);
                    }
                } else {
                    $template_select->addOption(rex_i18n::msg('option_no_template'), '0');
                }
                $TEMPLATE_NAME[0] = rex_i18n::msg('template_default_name');
                $tmpl_head = '<th>' . rex_i18n::msg('header_template') . '</th>';
            }
            

            // --------------------- ARTIKEL LIST
            $art_add_link = '';
            if ($KATPERM) {
                $art_add_link = '<a href="' . $context->getUrl(['function' => 'add_art', 'artstart' => $artstart]) . '"' . rex::getAccesskey(rex_i18n::msg('article_add'), 'add_2') . '><i class="rex-icon rex-icon-add-article"></i></a>';
            }

            // ---------- COUNT DATA
            $sql = rex_sql::factory();
            $where = '((parent_id=' . $category_id . ' AND startarticle=0) OR (id=' . $category_id . ' AND startarticle=1))
                    AND clang_id=' . $clang;
            // $sql->setDebug();
            $sql->setQuery('SELECT COUNT(*) as artCount FROM ' . rex::getTable('article') . ' WHERE ' . $where );

            // --------------------- ADD PAGINATION

            $artPager = new rex_pager($config['items_per_page'], 'artstart');
            $artPager->setRowCount($sql->getValue('artCount'));
            $artFragment = new rex_fragment();
            $artFragment->setVar('urlprovider', $context);
            $artFragment->setVar('pager', $artPager);
            
            $pager = '';
            if ($config['items_per_page']) {
                $pager = $artFragment->parse('core/navigations/pagination.php');
            }
            
            $qry = 'SELECT *
                FROM
                    ' . rex::getTable('article') . '
                WHERE ' . $where . '
                ORDER BY '.$config['additional_db_column'].' '. $config['order_direction'];
            if ($config['items_per_page']) {
                $qry .= ' LIMIT ' . $artPager->getCursor() . ',' . $artPager->getRowsPerPage();
            }

            // ---------- READ DATA
            $sql->setQuery($qry);

            // ----------- PRINT OUT THE ARTICLES
             
            $additional_head = $show_additional_column ? '<th>'.$config['additional_column_label'].'</th>' : '';

            $echo .= '
            <style>
               tr td.color_online { background-color: '.rex_config::get('structure_plus','color_online').' }
               tr td.color_future { background-color: '.rex_config::get('structure_plus','color_future').' }
               tr td.color_offline { background-color: '.rex_config::get('structure_plus','color_offline').' }
               tr td.color_gone { background-color: '.rex_config::get('structure_plus','color_gone').' }
               tr td.color_disabled { background-color: '.rex_config::get('structure_plus','color_disabled').' }
            </style>
            <table class="table table-striped table-hover tablesorter" id="sp_table">
                <thead>
                    <tr>
                        <th class="rex-table-icon">' . $art_add_link . '</th>
                        <th class="rex-table-id">' . rex_i18n::msg('header_id') . '</th>
                        <th>' . rex_i18n::msg('header_article_name') . '</th>
                        ' . $tmpl_head . '
                        <th>' . rex_i18n::msg('header_date') . '</th>
                        <th class="rex-table-priority">' . rex_i18n::msg('header_priority') . '</th>
                        '.$additional_head.'
                        <th class="rex-table-action" colspan="3">' . rex_i18n::msg('header_status') . '</th>
                    </tr>
                </thead>
                ';

            // tbody nur anzeigen, wenn später auch inhalt drinnen stehen wird
            if ($sql->getRows() > 0 || $function == 'add_art') {
                $echo .= '<tbody>
                    ';
            }

            // --------------------- ARTIKEL ADD FORM
            if ($function == 'add_art' && $KATPERM) {
                $tmpl_td = '';
                if ($withTemplates) {
                    $selectedTemplate = 0;
                    if ($category_id) {
                        // template_id vom Startartikel erben
                        $sql2 = rex_sql::factory();
                        $sql2->setQuery('SELECT template_id FROM ' . rex::getTablePrefix() . 'article WHERE id=' . $category_id . ' AND clang_id=' . $clang . ' AND startarticle=1');
                        if ($sql2->getRows() == 1) {
                            $selectedTemplate = $sql2->getValue('template_id');
                        }
                    }
                    if (!$selectedTemplate || !isset($TEMPLATE_NAME[$selectedTemplate])) {
                        $selectedTemplate = rex_template::getDefaultId();
                    }
                    if ($selectedTemplate && isset($TEMPLATE_NAME[$selectedTemplate])) {
                        $template_select->setSelected($selectedTemplate);
                    }

                    $tmpl_td = '<td data-title="' . rex_i18n::msg('header_template') . '">' . $template_select->get() . '</td>';
                }

                $echo .= '<tr class="mark">
                    <td class="rex-table-icon"><i class="rex-icon rex-icon-article"></i></td>
                    <td class="rex-table-id" data-title="' . rex_i18n::msg('header_id') . '">-</td>
                    <td data-title="' . rex_i18n::msg('header_article_name') . '"><input class="form-control" type="text" name="article-name" autofocus /></td>
                    ' . $tmpl_td . '
                    <td data-title="' . rex_i18n::msg('header_date') . '">' . rex_formatter::strftime(time(), 'date') . '</td>
                    <td class="rex-table-priority" data-title="' . rex_i18n::msg('header_priority') . '"><input class="form-control" type="text" name="article-position" value="' . ($artPager->getRowCount() + 1) . '" /></td>
                    <td class="rex-table-action" colspan="3">' . rex_api_article_add::getHiddenFields() . '<button class="btn btn-save" type="submit" name="artadd_function"' . rex::getAccesskey(rex_i18n::msg('article_add'), 'save') . '>' . rex_i18n::msg('article_add') . '</button></td>
                </tr>
                            ';
            }

            // --------------------- ARTIKEL LIST

            for ($i = 0; $i < $sql->getRows(); ++$i) {
                if ($sql->getValue('id') == rex_article::getSiteStartArticleId()) {
                    $class = ' rex-icon-sitestartarticle';
                } elseif ($sql->getValue('startarticle') == 1) {
                    $class = ' rex-icon-startarticle';
                } else {
                    $class = ' rex-icon-article';
                }

                $class_startarticle = '';
                if ($sql->getValue('startarticle') == 1) {
                    $class_startarticle = ' rex-startarticle';
                }

                // --------------------- ARTIKEL EDIT FORM

                if ($function == 'edit_art' && $sql->getValue('id') == $article_id && $KATPERM) {
                    $tmpl_td = '';
                    if ($withTemplates) {
                        $template_select->setSelected($sql->getValue('template_id'));
                        $tmpl_td = '<td data-title="' . rex_i18n::msg('header_template') . '">' . $template_select->get() . '</td>';
                    }
                    $echo .= '<tr class="mark' . $class_startarticle . '">
                            <td class="rex-table-icon"><a href="' . $context->getUrl(['page' => 'content/edit', 'article_id' => $sql->getValue('id')]) . '" title="' . rex_escape($sql->getValue('name')) . '"><i class="rex-icon' . $class . '"></i></a></td>
                            <td class="rex-table-id" data-title="' . rex_i18n::msg('header_id') . '">' . $sql->getValue('id') . '</td>
                            <td data-title="' . rex_i18n::msg('header_article_name') . '"><input class="form-control" type="text" name="article-name" value="' . rex_escape($sql->getValue('name')) . '" autofocus /></td>
                            ' . $tmpl_td . '
                            <td data-title="' . rex_i18n::msg('header_date') . '">' . rex_formatter::strftime($sql->getDateTimeValue('createdate'), 'date') . '</td>
                            <td class="rex-table-priority" data-title="' . rex_i18n::msg('header_priority') . '"><input class="form-control" type="text" name="article-position" value="' . rex_escape($sql->getValue('priority')) . '" /></td>
                            <td class="rex-table-action" colspan="3">' . rex_api_article_edit::getHiddenFields() . '<button class="btn btn-save" type="submit" name="artedit_function"' . rex::getAccesskey(rex_i18n::msg('article_save'), 'save') . '>' . rex_i18n::msg('article_save') . '</button></td>
                        </tr>';
                } elseif ($KATPERM) {
                    // --------------------- ARTIKEL NORMAL VIEW | EDIT AND ENTER

                    $article_status = $artStatusTypes[$sql->getValue('status')][0];
                    $article_class = $artStatusTypes[$sql->getValue('status')][1];
                    $article_icon = $artStatusTypes[$sql->getValue('status')][2];

                    $add_extra = '';
                    if ($sql->getValue('startarticle') == 1) {
                        $add_extra = '<td class="rex-table-action"><span class="text-muted"><i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('delete') . '</span></td>
                              <td class="rex-table-action"><span class="' . $article_class . ' text-muted"><i class="rex-icon ' . $article_icon . '"></i> ' . $article_status . '</span></td>';
                    } else {
                        if ($KATPERM && rex::getUser()->hasPerm('publishArticle[]')) {
                            $article_status = '<a class="' . $article_class . '" href="' . $context->getUrl(['article_id' => $sql->getValue('id'), 'artstart' => $artstart] + rex_api_article_status::getUrlParams()) . '"><i class="rex-icon ' . $article_icon . '"></i> ' . $article_status . '</a>';
                        } else {
                            $article_status = '<span class="' . $article_class . ' text-muted"><i class="rex-icon ' . $article_icon . '"></i> ' . $article_status . '</span>';
                        }

                        $article_delete = '<a href="' . $context->getUrl(['article_id' => $sql->getValue('id'), 'artstart' => $artstart] + rex_api_article_delete::getUrlParams()) . '" data-confirm="' . rex_i18n::msg('delete') . ' ?"><i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('delete') . '</a>';

                        $add_extra = '<td class="rex-table-action">' . $article_delete . '</td>
                              <td class="rex-table-action">' . $article_status . '</td>';
                    }

                    $editModeUrl = $context->getUrl(['page' => 'content/edit', 'article_id' => $sql->getValue('id'), 'mode' => 'edit']);

                    $tmpl_td = '';
                    if ($withTemplates) {
                        $tmpl = isset($TEMPLATE_NAME[$sql->getValue('template_id')]) ? $TEMPLATE_NAME[$sql->getValue('template_id')] : '';
                        $tmpl_td = '<td data-title="' . rex_i18n::msg('header_template') . '">' . $tmpl . '</td>';
                    }
                    
                    $class_additional = self::get_row_class($sql->getValue($config['additional_db_column']),$sql->getValue('art_online_to'),$sql->getValue('status'));
                    
                    $additional_col = $show_additional_column ? '<td class="'.$class_additional.'">' . self::get_field_value(rex_escape($sql->getValue($config['additional_db_column']))) . '</td>' : '';
                    

                    $echo .= '<tr' . (($class_startarticle != '') ? ' class="' . trim($class_startarticle) . '"' : '') . '>
                            <td class="rex-table-icon"><a href="' . $editModeUrl . '" title="' . rex_escape($sql->getValue('name')) . '"><i class="rex-icon' . $class . '"></i></a></td>
                            <td class="rex-table-id" data-title="' . rex_i18n::msg('header_id') . '">' . $sql->getValue('id') . '</td>
                            <td data-title="' . rex_i18n::msg('header_article_name') . '"><a href="' . $editModeUrl . '">' . rex_escape($sql->getValue('name')) . '</a></td>
                            ' . $tmpl_td . '
                            <td data-title="' . rex_i18n::msg('header_date') . '">' . rex_formatter::strftime($sql->getDateTimeValue('createdate'), 'date') . '</td>
                            <td class="rex-table-priority" data-title="' . rex_i18n::msg('header_priority') . '">' . rex_escape($sql->getValue('priority')) . '</td>
                            '.$additional_col.'
                            <td class="rex-table-action"><a href="' . $context->getUrl(['article_id' => $sql->getValue('id'), 'function' => 'edit_art', 'artstart' => $artstart]) . '"><i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('change') . '</a></td>
                            ' . $add_extra . '
                        </tr>
                        ';
                } else {
                    // --------------------- ARTIKEL NORMAL VIEW | NO EDIT NO ENTER

                    $art_status = $artStatusTypes[$sql->getValue('status')][0];
                    $art_status_class = $artStatusTypes[$sql->getValue('status')][1];
                    $art_status_icon = $artStatusTypes[$sql->getValue('status')][2];

                    $tmpl_td = '';
                    if ($withTemplates) {
                        $tmpl = isset($TEMPLATE_NAME[$sql->getValue('template_id')]) ? $TEMPLATE_NAME[$sql->getValue('template_id')] : '';
                        $tmpl_td = '<td data-title="' . rex_i18n::msg('header_template') . '">' . $tmpl . '</td>';
                    }

                    $echo .= '<tr>
                            <td class="rex-table-icon"><i class="rex-icon' . $class . '"></i></td>
                            <td class="rex-table-id" data-title="' . rex_i18n::msg('header_id') . '">' . $sql->getValue('id') . '</td>
                            <td data-title="' . rex_i18n::msg('header_article_name') . '">' . rex_escape($sql->getValue('name')) . '</td>
                            ' . $tmpl_td . '
                            <td data-title="' . rex_i18n::msg('header_date') . '">' . rex_formatter::strftime($sql->getDateTimeValue('createdate'), 'date') . '</td>
                            <td class="rex-table-priority" data-title="' . rex_i18n::msg('header_priority') . '">' . rex_escape($sql->getValue('priority')) . '</td>
                            <td>' . self::get_field_value(rex_escape($sql->getValue($config['additional_db_column']))) . '</td>
                            <td class="rex-table-action"><span class="text-muted"><i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('change') . '</span></td>
                            <td class="rex-table-action"><span class="text-muted"><i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('delete') . '</span></td>
                            <td class="rex-table-action"><span class="' . $art_status_class . ' text-muted"><i class="rex-icon ' . $art_status_icon . '"></i> ' . $art_status . '</span></td>
                        </tr>';
                }

                $sql->next();
            }

            // tbody nur anzeigen, wenn später auch inhalt drinnen stehen wird
            if ($sql->getRows() > 0 || $function == 'add_art') {
                $echo .= '
                </tbody>';
            }

            $echo .= '
            </table>';

        }
        
        return [$echo,$pager];



//        echo 'huhu huhu huhu';
    }
    
    private static function get_field_value($value) {
        $field_type = rex_config::get('structure_plus','field_type');
        switch ($field_type) {
            case 'date':
                return $value ? date('d.m.Y',$value) : '';
                break;
            case 'timestamp':
                return $value ? date('d.m.Y H:m:s',$value) : '';
                break;

            default:
                return $value;
                break;
        }
        
        
    }
    
    
    public static function get_row_class ($online_from,$online_to,$status) {
        if (in_array(rex_config::get('structure_plus','field_type'),['date','timestamp'])) {
            if (!$online_from && !$online_to) {
                return '';
            }
            if ($status == 0) {
                return 'color_offline';
            }
            if ($status == 2) {
                return 'color_disabled';
            }
            if ($online_to && $online_to < time()) {
                return 'color_gone';
            }
            if ($online_from > time()) {
                return 'color_future';
            }
            return 'color_online';
        }
        return '';
    }

}
