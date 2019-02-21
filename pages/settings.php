<?php


$config = rex_request('config', array(
    array('func', 'string'),
    array('submit', 'boolean'),
));

$form = '';

if ($config['submit']) {
    $cfg = rex_request('config','array');
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

// gültig für Kategorien ...

$formElements = [];
$n = [];
$n['label'] = '<label>Sortierung wird für diese Kategorien verwendet</label>';
$n['field'] = rex_var_linklist::getWidget(1, 'config[for_categories]',$this->getConfig('for_categories'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$form .= $fragment->parse('core/form/container.php');


// Anzahl Einträge pro Seite

$formElements = [];
$n = [];
$n['label'] = '<label for="structure_plus_items_per_page">Anzahl Einträge pro Seite</label>';
$n['field'] = '<input class="form-control" id="structure_plus_items_per_page" type="text" name="config[items_per_page]" value="' . $this->getConfig('items_per_page') . '" />';
$n['note'] = '0 für alle eintragen. Standardwert in REDAXO ist 30';
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

