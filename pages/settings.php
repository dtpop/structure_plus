<?php


$config = rex_request('config', array(
    array('func', 'string'),
    array('submit', 'boolean'),
));

$form = '';
$minicolors = rex_plugin::get('ui_tools','jquery-minicolors')->getProperty('status') ? ' ud_minicolors' : '';

if ($config['submit']) {
    $cfg = rex_request('config','array');
    if (!isset($cfg['for_all_categories'])) {
        $cfg['for_all_categories'] = 0; // checkbox
    }
    foreach ($cfg as $k=>$var) {
        if ($k != 'submit') {
            $this->setConfig($k,$var);            
        }
    }
    $form .= rex_view::info('Werte gespeichert');
}

// open form
$form .= '
    <form action="' . rex_url::currentBackendPage() . '" method="post">
        <fieldset>
        <legend>Structure Plus - Einstellungen</legend>
';


$fragment = new rex_fragment();


// Sortierung / Zusatzspalte
$formElements = [];
$sel = new rex_select();
$sel->setId('structure_plus_additional_db_column');
$sel->setName('config[additional_db_column]');
$sel->setSize(1);
$sel->setAttribute('class', 'form-control selectpicker');
$sel->setSelected($this->getConfig('additional_db_column'));
$sql = rex_sql::factory();
$sql->setQuery('SELECT * FROM '.rex::getTable('article'));
$res = $sql->getFieldnames();
$options = [];
$sel->addOption('-- keine --','');
foreach ($res as $r) {
    $sel->addOption($r, $r);
}
$n = [];
$n['label'] = '<label for="structure_plus_additional_db_column">Spalte, nach der Artikel in der Struktur sortiert werden</label>';
$n['field'] = $sel->get();
$formElements[] = $n;
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/container.php');


// Label der Spalte

$formElements = [];
$n = [];
$n['label'] = '<label for="structure_plus_additional_column_label">Name der Zusatzspalte</label>';
$n['field'] = '<input class="form-control" id="structure_plus_additional_column_label" type="text" name="config[additional_column_label]" value="' . $this->getConfig('additional_column_label') . '" />';
$n['note'] = 'Der Name wird im Kopf der Tabelle angezeigt';
$formElements[] = $n;
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/container.php');

// Aufsteigend / Absteigend

$formElements = [];
$sel = new rex_select();
$sel->setId('structure_plus_order_direction');
$sel->setName('config[order_direction]');
$sel->setSize(1);
$sel->setAttribute('class', 'form-control selectpicker');
$sel->setSelected($this->getConfig('order_direction'));
$options = [
    'ASC'=>'Aufsteigend',
    'DESC'=>'Absteigend'
];
foreach ($options as $k=>$v) {
    $sel->addOption($v, $k);
}
$n = [];
$n['label'] = '<label for="structure_plus_order_direction">Sortierreihenfolge</label>';
$n['field'] = $sel->get();
$n['note'] = 'Die Artikel können aufsteigend oder absteigend sortiert werden';
$formElements[] = $n;
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/container.php');

// Typ: Timestamp (Datum / Zeit), Datum, Integer, String

$formElements = [];
$sel = new rex_select();
$sel->setId('structure_plus_field_type');
$sel->setName('config[field_type]');
$sel->setSize(1);
$sel->setAttribute('class', 'form-control selectpicker');
$sel->setSelected($this->getConfig('field_type'));
$options = [
    'date'=>'Datum (20.05.1980)',
    'timestamp'=>'Zeit (20.05.1980 13:55:00)',
    'int'=>'Zahl',
    'string'=>'Text',
];
foreach ($options as $k=>$v) {
    $sel->addOption($v, $k);
}
$n = [];
$n['label'] = '<label for="structure_plus_field_type">Feldtyp</label>';
$n['field'] = $sel->get();
$n['note'] = 'Bei der Einstellung Datum oder Zeit wird ein Timestamp Wert umgewandelt';
$formElements[] = $n;
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/container.php');

// gültig für alle Kategorien ...

$formElements = [];
$n = [];
$n['label'] = '<label for="structure_plus_for_all_categories">Kategorien ausschließen</label>';
$n['field'] = '<input type="checkbox" id="structure_plus_for_all_categories" name="config[for_all_categories]" value="1" ' . ($this->getConfig('for_all_categories') ? ' checked="checked"' : '') . ' />';
$n['note'] = 'Bei aktivierter Checkbox werden alle Kategorien angepasst mit Ausnahme der ausgewählten Kategorien im nächsten Feld. D.h. um die Anzeige sämtlicher Artikel anzupassen, bitte die Checkbox aktivieren und keine Kategorien auswählen.<br>Bei nicht aktivierter Checkbox werden lediglich die unten ausgewählten Kategorien angepasst.';
$formElements[] = $n;
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/container.php');

// gültig für Kategorien ...

$formElements = [];
$n = [];
$n['label'] = '<label id="sp_for_categories_label">Kategorien</label>';
$n['field'] = rex_var_linklist::getWidget(1, 'config[for_categories]',$this->getConfig('for_categories'));
$formElements[] = $n;
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/container.php');


// Anzahl Einträge pro Seite

$formElements = [];
$n = [];
$n['label'] = '<label for="structure_plus_items_per_page">Anzahl Einträge pro Seite</label>';
$n['field'] = '<input class="form-control" id="structure_plus_items_per_page" type="text" name="config[items_per_page]" value="' . $this->getConfig('items_per_page') . '" />';
$n['note'] = '0 für alle eintragen. Standardwert in REDAXO ist 30. Werte kleiner als 30 werden ignoriert.';
$formElements[] = $n;
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/container.php');


// Farben

$formElements = [];
$n = [];
$n['label'] = '<label for="structure_plus_color_online">Farbe online</label>';
$n['field'] = '<input class="form-control '.$minicolors.'" id="structure_plus_color_online" type="text" name="config[color_online]" value="' . $this->getConfig('color_online') . '" />';
$formElements[] = $n;
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/container.php');

$formElements = [];
$n = [];
$n['label'] = '<label for="structure_plus_color_offline">Farbe Offline</label>';
$n['field'] = '<input class="form-control '.$minicolors.'" id="structure_plus_color_offline" type="text" name="config[color_offline]" value="' . $this->getConfig('color_offline') . '" />';
$formElements[] = $n;
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/container.php');

$formElements = [];
$n = [];
$n['label'] = '<label for="structure_plus_color_disabled">Farbe Gesperrt</label>';
$n['field'] = '<input class="form-control '.$minicolors.'" id="structure_plus_color_disabled" type="text" name="config[color_disabled]" value="' . $this->getConfig('color_disabled') . '" />';
$formElements[] = $n;
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/container.php');

$formElements = [];
$n = [];
$n['label'] = '<label for="structure_plus_color_future">Farbe Zukunft</label>';
$n['field'] = '<input class="form-control '.$minicolors.'" id="structure_plus_color_future" type="text" name="config[color_future]" value="' . $this->getConfig('color_future') . '" />';
$formElements[] = $n;
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/container.php');

$formElements = [];
$n = [];
$n['label'] = '<label for="structure_plus_color_gone">Farbe Abgelaufen</label>';
$n['field'] = '<input class="form-control '.$minicolors.'" id="structure_plus_color_gone" type="text" name="config[color_gone]" value="' . $this->getConfig('color_gone') . '" />';
$formElements[] = $n;
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/container.php');





$form .= '</fieldset>';

$form .= '<fieldset>'
        . '<legend></legend>';



// create submit button
$formElements = array();
$elements = array();
$elements['field'] = '
  <input type="submit" class="btn btn-save rex-form-aligned" name="config[submit]" value="Einstellungen übernehmen" ' . rex::getAccesskey(rex_i18n::msg('sked_config_save'), 'save') . ' />
';
$formElements[] = $elements;

// parse submit element
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/submit.php');

// close form
$form .= '
    </fieldset>
  </form>
';

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit');
$fragment->setVar('title', 'Einstellung');
$fragment->setVar('body', $form, false);
echo $fragment->parse('core/page/section.php');

?>

<?php if ($minicolors) : ?>
<script type="text/javascript">
    /*
    $(document).on('rex:ready',function() {
        show_hide_categories ();
    });
    
    $(document).on('change','input#structure_plus_for_all_categories',function() {
        show_hide_categories();
    });
    
    
    function show_hide_categories () {
        $('#sp_for_categories_label').parents('.rex-form-container').show();
        if ($('input#structure_plus_for_all_categories').prop('checked')) {
            $('#sp_for_categories_label').parents('.rex-form-container').hide();            
        }
    }
     */
    
    $('.ud_minicolors').minicolors(
        {
            theme: 'bootstrap',
            position: 'bottom right',
        }
    );
    
    
</script>
<?php endif ?>