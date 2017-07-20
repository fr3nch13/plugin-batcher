# Legacy Plugin: Batcher


Add the ability to batch add records to your database.
It works like multi-step wizard that that allows you to map the columns in a CSV or Excel file to the fields in your database.
It also supports a CSV dump.


## Usage

### Config
In your `app/Config/bootstrap.php` add the following line:

```php
CakePlugin::load('Batcher');
```

---
### Model setup

In your model, or app model, add this plugin's Behavior like this:
  
```javascript
public $actsAs = array(
		'Batcher.Batcher' => array(
			'fieldMap' => array(
				'Subnet.name' => array('label' => 'Name'),
				'Subnet.cidr' => array('label' => 'CIDR'),
				'Subnet.ic' => array('label' => 'IC'),
				'Subnet.location' => array('label' => 'Location'), 
				'Subnet.comments' => array('label' => 'Comments'),
				'Subnet.dhcp' => array('label' => 'DHCP', 'type' => 'match', 
					'default' => 0,
					'options' => array(
						'yes' => 1,
						'no' => 2,
					)),
				'Subnet.dhcp_scope' => array('label' => 'DHCP Scope'),
			),
		),
	);
```
  
  
The `fieldMap` is a list of fields in your database that can be mapped.
If the field isn't in this list, it can't be mapped by this plugin.
It MUST include the ModelName.field dot method of referencing database fields.

The attributes for each field in the map are as follows:

<dl>
<dt><code>label</code></dt>
<dd>
&nbsp; &nbsp; &nbsp;The human readable name that shows up in the wizard pages.
</dd>

<dt><code>type</code></dt>
<dd>
&nbsp; &nbsp; &nbsp;The type of field this is, the default is 'text'.<br/>
&nbsp; &nbsp; &nbsp;One type is <code>match</code>. This allows you to overwrite the incoming text with something that can match your database field.<br/>
&nbsp; &nbsp; &nbsp;In the above example `Subnet.dhcp` in an integer in the database.
</dd>

<dt><code>options</code></dt>
<dd>
&nbsp; &nbsp; &nbsp;The available options that the incoming text can be.<br/>
&nbsp; &nbsp; &nbsp;The incoming text is transformed by <code>Inflector::slug(strtolower('[text]'))</code><br />
&nbsp; &nbsp; &nbsp;before it is compared to the array keys in the <code>options</code>. <br/>
&nbsp; &nbsp; &nbsp;If one matches, the incoming text is changed to the value in the array. <br/>
&nbsp; &nbsp; &nbsp;Example: <code>[text]</code> matches <code>yes</code>, so the new value if <code>[text]</code> is <code>1</code>.
</dd>

<dt><code>default</code></dt>
<dd>
&nbsp; &nbsp; &nbsp;For <code>match</code>, it's the default value if no option can be matched.
</dd>
</dl>

---
### Controller setup

In your controller, or app controller, add this plugin's Component like this:

```javascript
	public $components = array(
		'Batcher' => array(
			'className' => 'Batcher.Batcher',
			'objectName' => 'Subnet',
			'objectsName' => 'Subnets',
		),
	);
```
The `objectName` and `objectsName` refer to the name of the records you're adding.  
These are human readable and display on the frontend.
For example if you were adding to the table `high_schools`, using the model `HighSchools`, you'd set 
`objectName` to `High School` and `objectsName` to `High Schools`.


Then in the list of actions, add below.  
Note, I have the `admin_` prefix. This isn't needed, and you can use what ever you want.  
However, the caveat is that whatever prefix you choose, they all have to use it, or you'll get an error.

```javascript
	public function admin_batcher_step1() 
	{
		$this->Batcher->batcher_step1();
	}
	
	public function admin_batcher_step2() 
	{
		$this->Batcher->batcher_step2();
	}
	
	public function admin_batcher_step3() 
	{
		$this->Batcher->batcher_step3();
	}
	
	public function admin_batcher_step4() 
	{
		return $this->redirect(array('action' => 'index'));
	}
```# plugin-batcher
