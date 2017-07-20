<?php 
// File: Plugin/Batcher/View/Elements/batch_step1.ctp
$this->Html->css('Batcher.batchform', array('inline' => false));
$this->set('step_active', 1);
?>
<div class="top">
	<h1><?php echo __('Batch Import %s', $objectsName); ?></h1>
</div>
<div class="center batchform">
	<?php echo $this->element('Batcher.batcher_step_tabs') ?>
	<div class="batch_parts">
		<div class="batch_part form">
			<h3><?php echo __('The Form'); ?></h3>
			<?php echo $this->Form->create(array('type' => 'file'));?>
				<fieldset>
					<?php
					echo $this->Form->input('file', array(
						'type' => 'file',
						'label' => __('The %s or %s File.', __('Excel'), __('CSV')),
						'between' => __('(This will be used over the dump text below.)'),
					));
					
					echo $this->Form->input('dump', array(
						'type' => 'textarea',
						'label' => __('OR: %s Text Dump', __('CSV')),
					));
				?>
				</fieldset>
			<?php echo $this->Form->end(__('Go to Step 2')); ?>
		</div>
		<div class="batch_part caveats">
			<h3><?php echo __('The Caveats'); ?></h3>
			<ul>
				<li><?php echo __('For CSV Text Dumps, the first line will always be considered the headers.'); ?></li>
				<li><?php echo __('Make sure none of the cells in your CSV or Excel file are merged into multiple cells. This will throw off the mapping of the data.'); ?></li>
			</ul>
		</div>
	</div>
</div>