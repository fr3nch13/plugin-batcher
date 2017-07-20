<?php 
// File: Plugin/Batcher/View/Elements/batch_step2.ctp
$this->Html->css('Batcher.batchform', array('inline' => false));
$this->set('step_active', 2);
?>
<div class="top">
	<h1><?php echo __('Batch Import %s', $objectsName); ?></h1>
</div>
<div class="center batchform ">
	<?php echo $this->element('Batcher.batcher_step_tabs') ?>
	<div class="batch_parts">
		<div class="form">
			<?php echo $this->Form->create();?>
				<fieldset>
				<?php
					echo $this->Form->input('review', array(
						'name' => 'data[review]',
						'label' => __('Review Options'),
						'between' => __('Would you like to review the records before they\'re added, or just save them.'),
						'options' => array(
							'review' => __('Review the records before saving them.'),
							'save' => __('Just save them.'),
						)
					));
		        
$th = array(
	'fieldMap' => array('content' => __('%s Fields', $objectName)),
	'header' => array('content' => __('Importing Headers')),
//	'compare' => array('content' => __('Compare to existing records.')),
);

$td = array();

$i = 0;
foreach($fieldMap as $fieldName => $fieldSettings)
{
	$input = $this->Form->input($fieldName, array(
		'label' => false,
		'options' => $batcherHeaders,
		'multiple' => false,
		'empty' => __('None'),
		'required' => false,
	));
	
	$labelOptions = array();
	$label = $fieldSettings['label'];
	if(isset($fieldSettings['unique']) and $fieldSettings['unique'])
	{
		$label .= ' *';
		$labelOptions['class'] = 'unique';
	}
	
	
	$label = $this->Form->label($fieldName, $label, $labelOptions);
/*
	$compare_options = $this->Form->input('compare.'. $key, array(
		'label' => false,
		'type' => 'select',
		'multiple' => false,
		'options' => array(
			0 => __('No'),
			1 => __('COMPARE/OVERWRITE if record exists'),
			2 => __('IGNORE if record exists'),
		),
		'required' => false,
		'default' => 1,
	));
*/
	
	$td[$i] = array(
		$label,
		$input,
	);
	$i++;
}

echo $this->element('Utilities.table', array(
	'th' => $th,
	'td' => $td,
	'table_caption' => __('* Means this field will be used to try to find an existing record to update, instead of creating a new one.'),
	'use_search' => false,
	'use_pagination' => false,
	'show_refresh_table' => false,
	'use_row_highlighting' => false,
)); 
		        ?>
				</fieldset>
			<?php echo $this->Form->end(__('Go to Step 3')); ?>
		</div>
	</div>
</div>