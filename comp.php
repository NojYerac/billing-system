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

function textareaify($id, $addnl_attrs) {
	$label = '';
	if (!isset($addnl_attrs['innerHTML'])) {
		$addnl_attrs['innerHTML'] = '';
	}
    $addnl_attrs = array_merge(array('name' => $id), $addnl_attrs);
	//Create label tag if called for.'
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
    //assemble array for tagification.
    $tag_defs = array_merge(
        array(
        'tag'    =>    'textarea',
        'id'    =>    $id),
        $addnl_attrs
    );
    return '<div class="input_container">' . $label . tagify($tag_defs) . '</div>';
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

function get_customer_selector(array $addnl_attrs=array(), $suffix='') {
    $customers = array_merge(
        array('Select a customer' => ''),
        get_visible_clients()
    );
    $addnl_attrs = array_merge(
        array(
                'label' => 'Customer: '
        ), $addnl_attrs
	);
	$id = "customer_selector$suffix";
    return selectify($id, $customers, $addnl_attrs);
}

function currency($num) {
	$num = round((float)$num, 2);
	$dec_pos = strpos((string)$num, '.');
	if (!$dec_pos) {
		$num = $num . '.00';
	} else {
		$num = substr($num . 0, 0, $dec_pos + 3);
	}
	return $num;
}

function get_project_options($customer_id) {
    $options = optionify(
        "project_options_null",
        'Select a project',
        '', array('selected' => 'selected')
    );
    $projects = get_sorted_documents(
        'projects',
		array('customer_id' => $customer_id, 'active' => true),
		array('_id' => -1)
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

function get_project_selector($required=false, $suffix='', $p_attr=array()) {
    $c_attr = array('onchange' => "getProjects('$suffix')");
    $p_attr = array_merge($p_attr, array( 'label' => 'Project: '));
    if ($required) {
        $c_attr['required'] = 'required';
        $p_attr['required'] = 'required';
    }
    $innerHTML = get_customer_selector($c_attr, $suffix) . '<br/>' .
        selectify("project_selector$suffix", array('Select a project' => ''), $p_attr);
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
	$script = "<script>statusBoxExpire = " .
		"setInterval(function() {toggleVisible('status_box');" .
	    "clearInterval(statusBoxExpire)}, 3000)</script>";
    $innerHTML = "<h2>Status</h2><hr/><p>$status</p>" .
		'<button onclick="toggleVisible(\'status_box\');' .
		'clearInterval(statusBoxExpire)">Close</button>' . $script;
    $status_box = tagify(array(
        'tag' => 'div',
        'id' => 'status_box',
        'class' => 'status-box center visible',
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
        'customer_id' => $customer_id,
        'start_time' => array(
            '$gt' => $min_time,
            '$lt' => $max_time
            )
        )
    );
    $time_rows = '';
    $project_names = array();
    foreach ($times as $time) {
        if (!isset($project_names[$time['project_id']])) {
            $project_names[$time['project_id']] = get_project_name_by_id($time['project_id']);
        }
        $time_id = (string)$time['_id'];
        $time_rows .= "<tr id=\"row_$time_id\">" . get_time_row(
            $time_id,
            $customer_id,
            $customer_name,
            $time['project_id'],
            $project_names[$time['project_id']],
            date_create_from_format('U', $time['start_time']->sec),
            date_create_from_format('U', $time['stop_time']->sec)
            ) . '</tr>';
    }
    return $time_rows;
}

function get_time_rows_by_project($project_id) {
    $project = get_all_documents('projects', array('project_id' => (new MongoId($project_id))));
    $project_name = $project['project_name'];
    $customer_id = $project['customer_id'];
    $customer_name = get_customer_name_by_id($customer_id);
    $times = get_all_documents('timer', array('project_id' => $project_id));
    $time_rows = '';
    foreach ($times as $time) {
        $time_rows .= get_time_row(
            (string)$time['_id'],
            $customer_id,
            $customer_name,
            $project_id,
            $project_name,
            date_create_from_format('U', $time['start_time']->sec),
            date_create_from_format('U', $time['stop_time']->sec)
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


function get_edit_invoice_div($invoice) {
	return tagify(array(
		'tag' => 'div',
		'id' => "edit_invoice_div_${invoice['_id']}",
		'class' => 'edit_invoice_div hidden',
		'innerHTML' => "<h4>${invoice['invoice_number']}</h4>" .
			tagify(array(
				'tag' => 'div',
				'id' => "edit_invoice_buttons_div_${invoice['_id']}",
				'innerHTML' => "<a href=\"edit-invoice.php?invoice_id=${invoice['_id']}\">" .
				"<button>Edit</button></a>" .
				"<button onclick=\"if (confirm('Delete invoice ${invoice['invoice_number']}'))" .
				"{ toggleVisible('edit_invoice_div_${invoice['_id']}');" .
				"deleteInvoice('${invoice['_id']}')}\">Delete</button>" .
				"<button onclick=\"toggleVisible('edit_invoice_div_${invoice['_id']}')\">" .
				"Cancel</button>"
				)
			)
		)
	);
}

function get_invoice_link($invoice) {
	return tagify(array(
		'tag' => 'li',
		'id' => "invoice_li_${invoice['_id']}",
		'onclick' => "toggleVisible('edit_invoice_div_${invoice['_id']}')",
		'class' => 'invoice_li ' . ($invoice['paid']?'paid':'unpaid') ,
		'innerHTML' => tagify(array(
			'tag' => 'a',
			//'target' => '_blank',
			'class' => 'invoice_link ' . ($invoice['paid']?'paid':'unpaid'),
			'href' => $invoice['url'],
			'innerHTML' => "${invoice['invoice_number']}.pdf",
			'id' => "invoice_link_${invoice['_id']}",
			)
		) . ' $' . $invoice['total']
		)
	) . get_edit_invoice_div($invoice);
}

function get_invoice_row(array $row_params, $id='') {
	$row = "<tr" . ($id?" id=\"row_$id\"":"") .
		($row_params['_id']?
		"ondblclick=\"editInvoiceRow('${row_params['_id']}')\" ":"") .
		"><td>${row_params['project_name']}</td><td>${row_params['notes']}</td>" .
		"<td>${row_params['quantity']} ${row_params['unit']}(s)</td>" .
		"<td>$${row_params['price']}/${row_params['unit']}</td>" .
		"<td>$" . currency($row_params['sub_total']) . "</td></tr>";
	return $row;
}

function new_line_item($project_id) {
	$project = get_one_document(
		'projects',
		array(
			'_id' => (new MongoId($project_id))
		)
	);
	return array(
		'project_name' => $project['project_name'],
		'notes' => $project['project_notes'],
		'price' => $project['project_price'],
		'quantity' => 0,
		'unit' => 'hour',
	);
}

function seconds_to_hours_rounded($seconds) {
	return round($seconds/(60*15))/4;
}

function get_invoice_rows($customer_id, $min_time, $max_time) {
	$billable_times = get_all_documents(
		'timer',
		array(
			'customer_id'=> $customer_id,
			'start_time' => array(
				'$gte' => prepare_datetime($min_time),
				'$lte' => prepare_datetime($max_time)
			)
		)
	);
	$line_items = array();

	foreach ($billable_times as $time) {
		$interval = ($time['stop_time']->sec) - ($time['start_time']->sec);
		if (!isset($line_items[$time['project_id']])) {
			$line_items[$time['project_id']] = new_line_item($time['project_id']);
		}
		$line_items[$time['project_id']]['quantity'] += $interval;
	}

	$total = 0;
	$rows = '';

	foreach ($line_items as $project_id => $item) {
		$item['quantity'] = seconds_to_hours_rounded((float)$item['quantity']);
		$item['sub_total'] =  (float)$item['price'] * (float)$item['quantity'];
		$total += $item['sub_total'];
		$rows .= get_invoice_row($item, 'proj_' . $project_id);
	}

	//allow for custom line items.
	$line_items = get_all_documents('custom_rows', array(
		'customer_id' => $customer_id,
		'month' => prepare_datetime($min_time)
		)
	);

	foreach ($line_items as $item) {
		$total += $item['sub_total'];
		$rows .= get_invoice_row($item, 'custom_' . $item['_id']);
	}
	return array('rows' => $rows, 'total' => $total);
}

?>
