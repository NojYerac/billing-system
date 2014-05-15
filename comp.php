<?php

function tagify($tag_defs) {
    /* Takes an associative array, where key = id and value is an array.
     * 'innerHTML' => Content
     * 'tag' => Tag type
     * $attribute => $value
     */
    $attrs = '';
    foreach ($tag_defs as $attr => $value) {
        if ($attr == 'innerHTML') {
            $innerHTML = $value;
        } elseif ($attr == 'tag') {
            $tag = $value;
        } else {
            $attrs .= sprintf(' %s="%s"', $attr, $value);
        }
    }
    if (isset($innerHTML) && gettype($innerHTML) != 'string') {
         var_dump($innerHTML);
         die();
    } 
    //special case for script tags.
    if ($tag == 'script' && !isset($innerHTML)) {
        $innerHTML = '';
    }
    if (isset($innerHTML)) {
        return sprintf('<%s>%s</%s>', $tag . $attrs, $innerHTML, $tag);
    } else {
        return sprintf('<%s/>', $tag . $attrs);
    }
}

function inputify($type, $id, $addnl_attrs) {
    /*only bother with name and lable if not a submit button.
     * addnl_attrs override other attributes!
     * By default 'name', 'for' and 'id' all have the same value.
     */
    $label = '';
    if ($type != 'submit') {
        $addnl_attrs = array_merge(array('name' => $id), $addnl_attrs);
        //Create label tag if called for.
        if (isset($addnl_attrs['label'])) {
            $label = tagify(
                array(
                    'tag'    =>    'label',
                    'for'    =>    $id, 
                    'innerHTML'=>    $addnl_attrs['label']
                )
            );
            unset($addnl_attrs['label']);
        }
    }
    //assemble array for tagification.
    $tag_defs = array_merge(
        array(
        'type'    =>    $type,
        'tag'    =>    'input',
        'id'    =>    $id),
        $addnl_attrs
    );
    return $label . tagify($tag_defs);
}

function optionify($id, $innerHTML, $value, $addnl_attrs) {
    /*create an option tag
     *
     */
    $tag_defs = array_merge(
        array(
            'tag' => 'option',
	    'id' => $id,
	    'innerHTML' => $innerHTML,
	    'value' => $value
        ),
        $addnl_attrs
    );
    return tagify($tag_defs);
}

function selectify($id, array $options, array $addnl_attrs=array()) {
    /*create a select with options
     *
     */
    //Create label tag if called for.
    if (isset($addnl_attrs['label'])) {
        $addnl_attrs = array_merge(array('name' => $id), $addnl_attrs);
        $label = tagify(
            array(
                'tag'    =>    'label',
                'for'    =>    $id, 
                'innerHTML'=>    $addnl_attrs['label']
            )
        );
        unset($addnl_attrs['label']);
    }
    //Create all the option tags.
    $innerHTML = '';
    foreach ($options as $text => $value) {
        $opt_id = $id . "_" . preg_replace('/\W/', '_', strtolower($text));
        $innerHTML .= optionify($opt_id, $text, $value, array());
    }
    $tag_defs = array_merge(
        array(
            'tag' => 'select',
            'id' => $id,
            'innerHTML' => $innerHTML,
        ),
        $addnl_attrs
    );
    return $label . tagify($tag_defs);
}

function formify($method, $action, $inputs, $addnl_attrs) {
    $innerHTML = '';
    if (gettype($inputs) == "array") {
        foreach ($inputs as $input) {
            $innerHTML .= $input;
        }
    } else {
        $innerHTML = $inputs;
    }
    $tag_defs = array_merge(
        array(
            'tag'    =>    'form',
            'method' =>    $method,
            'action' =>    $action,
            'innerHTML' =>    $innerHTML
        ), $addnl_attrs);
    return tagify($tag_defs);
}

function get_document($head, $body, $addnl_attrs) {
    $doctype = "<!DOCTYPE html>\n";
    $innerHTML = $head . $body;
    $tag_defs = array_merge(
        array(
            'tag'    =>    'html',
            'innerHTML' =>    $innerHTML
        ), $addnl_attrs);
    return $doctype . tagify($tag_defs);
}

function get_head($title, $tags, $addnl_attrs) {
    $innerHTML = tagify(array('tag' => 'title', 'innerHTML' => $title));
    if (gettype($tags == 'array')) {
        foreach ($tags as $tag) {
            $innerHTML .= $tag;
        }
    } else {
        $innerHTML = $tags;
    }
    $tag_defs = array_merge(
        array(
            'tag'    =>    'head',
            'innerHTML' =>    $innerHTML,
        ), $addnl_attrs);
    return tagify($tag_defs);
}

function get_body($tags, $addnl_attrs) {
    if (gettype($tags) == 'array') {
        $innerHTML = '';
        foreach ($tags as $tag) {
            $innerHTML .= $tag;
        }
    } else {
        $innerHTML = $tags;
    }
    $tag_defs = array_merge(
        array(
            'tag'    =>    'body',
            'innerHTML' =>    $innerHTML),
        $addnl_attrs);
    return tagify($tag_defs);
}

function get_default_head() {
    /*
     * 
     */
    $script_tags = array(
        tagify(array(
            'tag'    =>    'script',
            'src'    =>    BASE_URL . '/js/default.js',
            'type'    =>    'text/javascript'
        )) //additional script files
    );
    $css_tags = array(
        tagify(array(
            'tag'    =>    'link',
            'rel'    =>    'stylesheet',
            'type'    =>    'text/css',
            'href'    =>    BASE_URL . '/css/default.css'
        )) //additional style files
    );
    $meta_tags = array(
        tagify(array(
            'tag'    =>    'meta',
            'charset' =>    'UTF-8'
        )), 
        tagify(array(
            'tag'    =>    'meta',
            'name'    =>    'generator',
            'content' =>    VERSION
        )) //additional meta tags
    );
    return get_head(TITLE, array_merge($script_tags, $css_tags, $meta_tags), array());
}

function pprint($html) {
    $html = preg_replace(
        array('/>(?!\n)/', 
        '/(.+)</'),
        array(">\n",
        "$1\n<"),
        $html);
    return $html;
        
}

function get_customer_selector(array $addnl_attrs=array()) {
    $customers = array_merge(
        array('Select a customer' => ''),
        get_visible_clients()
    );
    $addnl_attrs = array_merge(
        array(
                'label' => 'Customer: '
        ), $addnl_attrs
    );
    return selectify('customer_selector', $customers, $addnl_attrs);
}

function get_project_options($customer_id) {
	$options = optionify(
		"project_options_null",
		'Select a project',
		'', array('selected' => 'selected')
	);
	$projects = get_all_documents(
		'projects',
		array('customer_id' => $customer_id, 'active' => true)
	);
	foreach ($projects as $doc) {
		$project_id = htmlentities($doc['_id']);
		$project_name = htmlentities($doc['project_name']);
		$options .= optionify(
			"project_option_$project_id",
			$project_name,
			$project_id, array());
	}
	return $options;
}

function get_project_selector($required=false) {
    $c_attr = array('onchange' => "getProjects()");
    $p_attr = array( 'label' => 'Project: ');
    if ($required) {
        $c_attr['required'] = 'required';
        $p_attr['required'] = 'required';
    }
    $innerHTML = get_customer_selector($c_attr) . '<br/>' .
        selectify('project_selector', array('Select a project' => ''), $p_attr);
    $selector = tagify(
        array(
            'tag' => 'div',
            'id' => 'project_selector_div',
            'innerHTML' => $innerHTML
        )
    );
    return $selector;
}

function get_status_box($status) {
    $innerHTML = "<h2>Status</h2><hr/><p>$status</p>" .
        '<button onclick="toggleVisible(\'status_box\')">Close</button>';
    $status_box = tagify(array(
        'tag' => 'div',
        'id' => 'status_box',
        'class' => 'feature-box visible',
        'innerHTML' => $innerHTML
        )
    );
    return $status_box;
}

function get_project_name_by_id($project_id) {
    return get_one_value(
    'projects',
    array('_id' => (new MongoId($project_id))),
    'project_name'
    );
}

function get_customer_name_by_id($customer_id) {
    return get_one_value(
        'clients',
        array('_id' => (new MongoId($customer_id))),
        'customer_name'
        );
}

function get_time_rows_by_customer_and_datetime($customer_id, $min_time, $max_time) {
    $customer_name = get_customer_name_by_id($customer_id);
    $times = get_all_documents('timer', array(
        'customer_id' => $customer_id),
        'start_time' => array(
            '$gt' => $min_time,
            '$lt' => $max_time
            )
        );
    $time_rows = '';
    $project_names = array();
    foreach ($times as $time) {
        if (!isset($project_names[$time['project_id']])) {
            $project_names[$time['project_id']] = get_project_name_by_id($time['project_id']);
        }
        $time_rows .= get_time_row(
            (string)$time['_id'],
            $customer_id,
            $customer_name,
            $time['project_id'],
            $project_names[$time['project_id']],
            $time['start_time'],
            $time['stop_time']
        );
    }
    return $time_rows;
}

function get_time_rows_by_project($project_id) {
    $project = get_all_documents('projects', array('project_id' => (new MongoId($project_id))));
    $project_name = $project['project_name'];
    $customer_id = $project['customer_id'];
    $customer_name = get_customer_name_by_id($customer_id);
    $times = get_all_documents('timer', array(
        'project_id' => $project_id
        );
    $time_rows = '';
    foreach ($times as $time) {
        $time_rows .= get_time_row(
            (string)$time['_id'],
            $customer_id,
            $customer_name,
            $project_id,
            $project_name,
            $time['start_time'], $time['stop_time']
        );
    }
    return $time_rows;
}
        

            
function get_time_row(
        $time_id,
        $customer_id,
        $customer_name,
        $project_id,
        $project_name,
        $start_time,
        $stop_time
        ) {
    //format values
    $format = 'Y-m-d H:i:s';
    $diff_time = $start_time->diff($stop_time);
    return "<td value=\"$customer_id\">$customer_name</td>" .
        "<td value=\"$project_id\">$project_name</td>" .
        "<td>" . $start_time->format($format) . "</td>" .
        "<td>" . $stop_time->format($format) . "</td>" .
        "<td>" . $diff_time->format('%H:%I:%S') . "</td>" .
        "<td style=\"background-color:green\"" .
        " onclick=\"getEditTimeRow('$time_id')\"/>" .
        "<td style=\"background-color:red\"" .
        " onclick=\"if (confirm('Delete row?')) {deleteTime('$time_id')}\"/>";
  
}

?>
