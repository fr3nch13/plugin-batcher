<?php

$step_active = (isset($step_active)?$step_active: false);

$step1_active = false;
$step2_active = false;
$step3_active = false;
$step4_active = false;

if($step_active == 1)
	$step1_active = true;
elseif($step_active == 2)
	$step2_active = true;
elseif($step_active == 3)
	$step3_active = true;
elseif($step_active == 4)
	$step4_active = true;

$step1 = __('1. Upload/dump Excel/CSV file, or CSV content.');
$step2 = __('2. Map Importing Headers to Record Fields.');
$step3 = __('3. Review Importing Data'); 
$step4 = __('4. Completed and Results');

if(in_array($step_active, array(2, 3, 4)))
	$step1 = $this->Html->link($step1, array('action' => 'batcher_step1'));

if(in_array($step_active, array(3, 4)))
	$step2 = $this->Html->link($step2, array('action' => 'batcher_step2'));

if(in_array($step_active, array(4)))
	$step3 = $this->Html->link($step3, array('action' => 'batcher_step3'));

?>

<div class="step_tabs"><div class="step step1 <?php echo ($step1_active?'step_active':''); ?>"><?php 
	echo $step1;
?></div><div class="step step2 <?php echo ($step2_active?'step_active':''); ?>"><?php 
	echo $step2;
?></div><div class="step step3 <?php echo ($step3_active?'step_active':''); ?>"><?php 
	echo $step3;
?></div><div class="step step3 <?php echo ($step4_active?'step_active':''); ?>"><?php 
	echo $step4;
?></div></div>