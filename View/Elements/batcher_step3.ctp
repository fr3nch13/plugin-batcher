<?php 
// File: Plugin/Batcher/View/Elements/batch_step3.ctp
$this->Html->css('Batcher.batchform', array('inline' => false));
$this->set('step_active', 3);
?>
<div class="top">
	<h1><?php echo __('Batch Import %s', $objectsName); ?></h1>
</div>
<div class="center batchform ">
	<?php echo $this->element('Batcher.batcher_step_tabs') ?>
	<div class="batch_parts">
		<div class="form">
			<?php echo $this->Form->create(); ?>
				<fieldset>
				<?php
$th = array(
	'num' => array('content' => __('#')),
	'remove' => array('content' => __('Remove')),
	'existing' => array('content' => __('Existing')),
);
foreach($fieldMap as $key => $field_settings)
{
	$label = Inflector::humanize($key);
	
	if(isset($field_settings['label']))
		$label = $field_settings['label'];
	$th[$key] = $label;
}

$td = array();
foreach($records as $i => $record)
{
	$line = '';
	$existing = '';
	$remove = $this->Form->button(__('Remove this Record'), array(
		'type' => 'button', 
		'class' => 'remove_record'
	));
	
	if($record['existing'])
	{
		$existing = __('Exists');
		$existing .= '<br/>'. $this->Html->link(__('View'), array('action' => 'view', $record[$modelAlias][$primaryKey], 'admin' => false, 'saa' => false, 'manager' => false), array('target' => 'batcherview'));
		
		if(isset($record[$modelAlias][$primaryKey]))
		{
			$existing .= $this->Form->input($i.'.'.$modelAlias.'.'.$primaryKey, array('type' => 'hidden', 'value' => $record[$modelAlias][$primaryKey]));
		}
	}
	
	$td[$i] = array(
		($i + 1),
		$remove,
		$existing,
	);
	
	$record = Hash::flatten($record);
	
	foreach($fieldMap as $key => $field_settings)
	{
		$cellOptions = array('id' => 'td-'. $i.'-'. Inflector::slug($key), 'class' => 'record');
		
		$label = false;
		if(isset($field_settings['label']))
			$label = $field_settings['label'];
		
		$input_options = array(
			'label' => false,
			'value' => (isset($record[$key])?$record[$key]:''),
		);
		
		if(isset($field_settings['type']))
		{
			if($field_settings['type'] == 'match')
			{
				$input_options['type'] = 'select';
				$input_options['empty'] = '[ select ]';
				
				if($input_options['value'])
					$input_options['value'] = $this->Common->slugify($input_options['value']);
				
				if(isset($field_settings['default']))
				{
					$input_options['default'] = $field_settings['default'];
				}
				
				$_options = array();
				
				if(isset($field_settings['options']))
					$_options = $field_settings['options'];
				
				if(!isset($field_settings['preserve_options']) or !$field_settings['preserve_options'])
				{
					$_options = array_flip($_options);
					
					foreach($_options as $_option_k => $_option_v)
					{
						$_options[$_option_k] = Inflector::humanize($_option_v);
					}
				}
				$input_options['options'] = $_options;
			}
		}
		
		$existing = false;
		if(isset($record['existing.'.$key]))
		{
			$existing = $record['existing.'.$key];
			
			// see if it is a seperate attribute from the belongsTo
			if(isset($field_settings['foreignKey']))
			{
				$assocDisplayKey = $field_settings['modelAlias'].'.'.$field_settings['field'];
				if(isset($record['existing.'.$assocDisplayKey]))
					$existing = $record['existing.'.$assocDisplayKey];
				if(!$existing)
					$existing = '';
				
			}

			if($this->Common->slugify($existing) != $this->Common->slugify($input_options['value']))
			{
				$cellOptions['class'] .= ' highlight';
			}
			
			$existing = $this->Html->tag('span', $existing);
			$keep_button = $this->Html->link(__('Keep Existing'), '#', array('class' => 'field_button keep_button'));
			$new_button = $this->Html->link(__('Use New Value'), '#', array('class' => 'field_button new_button'));
			$existing = $this->Html->tag('div', $existing. $keep_button. $new_button, array('class' => 'existing_value'));
		}
		
		$input = $this->Form->input($i.'.'.$key, $input_options);
		
		$td[$i][$key] = array(
			$existing. $input,
			$cellOptions
		);
	}
}

$caption = array(
	__('"Remove this Record" means to NOT import this whole record/row from the importing file to the database.'),
	__('The text in each column is the EXISTING value'),
	__('The input field in each column is the IMPORTING value that will overwrite the EXISTING value.'),
	__('Cells highlighted in red have a different IMPORTING value from the EXISTING value.'),
);

echo $this->element('Utilities.table', array(
	'th' => $th,
	'td' => $td,
	'table_caption' => implode('<br/>', $caption),
	'use_search' => false,
	'use_pagination' => false,
	'show_refresh_table' => false,
	'use_row_highlighting' => false,
)); 
?>

				</fieldset>
			<?php echo $this->Form->end(__('Go to Step 4')); ?>
		</div>
	</div>
</div>
<script type="text/javascript">

var submitResults = false;

$(document).ready(function()
{
	// make sure the inputs are enabled
	$('.batch_parts table.listings td div.input input[disabled]').each(function(){
		$(this).attr('disabled', 'disabled');
		
		$(this).parents('td').addClass('highlight');
		$(this).parents('td').find('a.field_button.keep_button').hide();
		$(this).parents('td').find('a.field_button.new_button').show();
	});
	$('.batch_parts table.listings td div.input select[disabled]').each(function(){
		$(this).attr('disabled', 'disabled');
		
		$(this).parents('td').addClass('highlight');
		$(this).parents('td').find('a.field_button.keep_button').hide();
		$(this).parents('td').find('a.field_button.new_button').show();
	});
	
	$('.batch_parts .remove_record').each(function(){
		var tr = $(this).parents('tr');
		
		tr.find('td').each(function(){
			$(this).addClass('nowrap');
		});
		
		$(this).on('click', function(event){
			event.preventDefault();
			tr.remove();
		});
	});
	
	// watch the keep/use new buttons
	$('.batch_parts a.field_button.keep_button').each(function(){
		$(this).on('click', function(event){
			event.preventDefault();
			
			// hide this button, and show the other button
			$(this).hide();
			$(this).parent().find('a.field_button.new_button').show();
			// disable the input field
			$(this).parents('td').find('div.input').each(function(){
				$(this).find('input').attr('disabled', 'disabled');
				$(this).find('select').attr('disabled', 'disabled');
			});
		});
	});
	
	// watch the keep/use new buttons
	$('.batch_parts a.field_button.new_button').each(function(){
		$(this).on('click', function(event){
			event.preventDefault();
			
			// hide this button, and show the other button
			$(this).hide();
			$(this).parent().find('a.field_button.keep_button').show();
			// disable the input field
			$(this).parents('td').find('div.input').each(function(){
				$(this).find('input').removeAttr('disabled');
				$(this).find('select').removeAttr('disabled');
			});
		});
	});
});//ready 

</script>