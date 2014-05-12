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
        array('Select a customer' => '0'),
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
		'0', array('selected' => 'selected')
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

function get_project_selector() {
    $innerHTML = get_customer_selector(array('onchange' => 'getProjects()')) . '<br/>' .
        selectify('project_selector', array('Select a project' => ''), array('label' => 'Project: '));
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

?>
