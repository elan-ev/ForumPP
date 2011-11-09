<?
foreach($categories as $cat_id => $cat) {
    $new_areas = array();
    foreach ($cat['areas'] as $id) {
        $new_areas[$id] = $areas[$id];
        unset($areas[$id]);
    }
    $categories[$cat_id]['areas'] = $new_areas;
}
?>
<script>
    var loaded_childs = Array();

    function deleteChilds(area_id) {
        var elem = 'area_' + area_id;
        var list = Element.childElements(elem);

        // ommit the first three elements when deleting; these are the name and action-icons
        for (var i = 3; i < list.length; i++) {
            list[i].remove();
        }
        delete loaded_childs[area_id];
    }

    function loadChilds(area_id) {
        // if not set load childs
        if (typeof(loaded_childs[area_id]) == 'undefined') {
            var elem = 'area_' + area_id;
            jQuery('#area_' + area_id).load('<?= PluginEngine::getUrl($plugin, array(), 'loadchilds') ?>&area_id=' + area_id);
            /*
            new Ajax.Request('<?= PluginEngine::getUrl($plugin, array(), 'loadchilds') ?>&area_id=' + area_id, {
                asynchronous: false,
                onSuccess: function(response) {
                    Element.insert(elem, response.responseText);
                }
            });
            */
            loaded_childs[area_id] = true;
        } 
        
        // if already loaded delete the childs
        else {
            deleteChilds(area_id);
        }
    }

    var copyid = '';

    function choose(id) {
        if (copyid != '') {
            $(copyid).src = '<?= $picturepath ?>/icons/cut.png';
            $('area_' + copyid).style.backgroundColor = 'transparent';
        }

        if (copyid == id) {
            copyid = '';
            return;
        } else {
            copyid = id;
            $(id).src = '<?= $picturepath ?>/icons/cut_red.png';
            $('area_' + id).style.backgroundColor = '#FFCCCC';
        }
    }

    function paste(id) {
        if (copyid == '' || copyid == id) return;
        if ($('area_' + id).descendantOf($('area_' + copyid))) return;

        // load the child elements only if they are not present already
        if (typeof(loaded_childs[id]) == 'undefined' && id != '0') {
            loadChilds(id);
        }

        new Ajax.Request('<?= PluginEngine::getUrl($plugin, array(), 'changeparent') ?>&topic_id=' + copyid + '&new_parent=' + id , {
            asynchronous:true,
            onSuccess: function(response) {
                ul = new Element('ul');
                ul.insert($('area_' + copyid));
                $('area_' + id).insert(ul);
            }
        });
    }

    function showTooltip(id, text) {
        $('tooltip').innerHTML = text;
        $('tooltip').show();
    }

    function hideTooltip() {
        $('tooltip').hide();
    }
    
    function getcords(e){
        $('tooltip').style.top = (Event.pointerY(e) - document.body.scrollTop + 20) + 'px';
        $('tooltip').style.left = (Event.pointerX(e) - document.body.scrollLeft + 15)+'px';
    }

    Event.observe(document, 'mousemove', getcords);
</script>
<div class="posting bg2">
    <span class="corners-top"><span></span></span>

    <div class="postbody" style="width:98%">
        <b><?= _("Areas - Threads - Postings") ?>:</b><br/>
        <p>
            <? foreach ((array)$categories as $cat) : ?>
            <b><?= $cat['name'] ?>:</b>
            <img onClick="paste('0')" title="<?= _("Zum Bereich 'keiner Kategorie zugeordnet' hinzuf&uuml;gen") ?>" src="<?= $picturepath ?>/icons/paste_plain.png">
            <ul>
                <? foreach ($cat['areas'] as $area) : ?>
                <li id="area_<?= $area['topic_id'] ?>">
                    <a href="javascript:loadChilds('<?= $area['topic_id']?>')"
                        onMouseOver="showTooltip('area_<?= $area['topic_id'] ?>', '<?= preg_replace(array("/'/", '/"/', '/&#039;/'), array("\\'", '&quot;', "\\'"), $area['description']) ?>')" 
                        onMouseOut="hideTooltip()"><?= $area['name'] ?></a>
                    &nbsp;&nbsp;
                    <img onClick="choose('<?= $area['topic_id'] ?>')" id="<?= $area['topic_id'] ?>" src="<?= $picturepath ?>/icons/cut.png" title="Diskussionsstrang ausschneiden">
                    <img onClick="paste('<?= $area['topic_id'] ?>')" title="Diskussionsstrang hier einf&uuml;gen" src="<?= $picturepath ?>/icons/paste_plain.png">
                </li>
                <? endforeach; ?>
            </ul>
            <? endforeach; ?>
    
            <div id="area_0">
            <b><?= _("Keiner Kategorie zugeordnet") ?>:</b>
            <img onClick="paste('0')" title="<?= _("Als Bereich einf&uuml;gen") ?>" src="<?= $picturepath ?>/icons/paste_plain.png">
            <ul>
                <? foreach ((array)$areas as $area) : ?>
                <li id="area_<?= $area['topic_id'] ?>">
                    <a href="javascript:loadChilds('<?= $area['topic_id']?>')"
                        onMouseOver="showTooltip('area_<?= $area['topic_id'] ?>', '<?= preg_replace(array("/'/", '/"/', '/&#039;/'), array("\\'", '&quot;', "\\'"), $area['description'])?>')" 
                        onMouseOut="hideTooltip()"><?= $area['name'] ?></a>
                    &nbsp;&nbsp;
                    <img onClick="choose('<?= $area['topic_id'] ?>')" id="<?= $area['topic_id'] ?>" src="<?= $picturepath ?>/icons/cut.png" title="Diskussionsstrang ausschneiden">
                    <img onClick="paste('<?= $area['topic_id'] ?>')" title="Diskussionsstrang hier einf&uuml;gen" src="<?= $picturepath ?>/icons/paste_plain.png">
                </li>
                <? endforeach; ?>
            </ul>
            </div>
        </p>
    </div>
    
    <div id="tooltip" style="display: none; position: fixed; border: 1px solid black; background-color: #DFDFFA"></div>

    <span class="corners-bottom"><span></span></span>
</div>
