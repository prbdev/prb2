 function activateAjaxForm(selector, output) {
    var submits = $(selector).getElements('button[type=submit]');
    if(submits.length) {
        submits.addEvent('click', function(evt) {
            evt.stop();
            if(window.ie){
                var tmpHTML = this.innerHTML; //Ugly IE hack
                this.innerHTML='';
            }
            var thisForm = $(selector);
            var out = $(output);
            var hidden = new Element('input', {type: 'hidden'}).inject(thisForm);
                hidden.setProperties({
                    name: this.name,
                    value: this.value
                });
            if(window.ie) this.innerHTML=tmpHTML; // Restore button beauty

            // Send the ajax request
            var req = new Request.HTML({
                url: thisForm.action,
                data: thisForm,
                method: 'post',
                evalScripts: true,
                update: out,
                onRequest: function() {
                    thisForm.set('html', '<div class=ajax-loading></div>');
                },
                onComplete: function() {
                    out.removeClass('ajax-loading');
                }
            }).send();

        });
    }
 }

 function ajxcontent( gid ) {
    var url = "ajax_backend/prb_be.php";
    var trid = findParent(gid);
    var chid = getLastChild('customtree', trid.id);
    var qrystring = 'act=details&viewid='+gid+'&trid='+trid.id+'&chid='+chid;
    var out = $('topContent').empty();
    out.addClass('ajax-loading');
        new Request.HTML({
            url: url,
            method: 'get',
            evalScripts: true,
            data: qrystring,
            update:out,
            onComplete: function() {
                out.removeClass('ajax-loading');
            }
        }).send();
 }

 function findParent(id) {
	return $(id).getParent().getParent();
 }

 function getLastChild(gid, pid) {
	var chid='';
	var elts = $(gid).getElements('tr[id^='+pid+'-]');
        var re = "^"+pid+"\-[0-9]+$";
        elts.each( function(tr) {
            if( tr.id.test(re) ) {
		        chid = tr.id;
            }
        });
	return chid;
 }

 // doExpand function
 function doExpand(pid, gid) {
        var re = "^"+pid+"\-[0-9]+$";
        $$('#'+gid+' tr').each( function(tr) {
            if( tr.id.test(re) ) {
                tr.removeClass('close');
                if( tr.hasClass('expand') ) {doExpand(tr.id, gid); }
             }
        });
 }

 // Expand all
 function doExpandAll(pid, gid) {
	var elts = $(gid).getElements('tr[id^='+pid+']');
        elts.each( function(tr) {
            tr.removeClass('close');
            tr.addClass('expand');
        });
 }

 function addTreegridRow(newid, trid) {
    var chid='';
    var tmp = $(newid).getParent();
    var kids = $('customtree').getElements('tr[id^='+trid+'-]');
    kids.each( function(tr) {
        chid=tr.id;
    });
    $(newid).injectAfter($(chid));
    doExpand(trid,'customtree');
    addTreeEvents(newid);
    $(tmp).remove();
    var gr = $E('span', newid);
    gr.addEvent('click', function() {
        ajxcontent(gr.id);
    });
 }

 function addSpanEvents(gid) {
    var grs = $$('#'+gid+' span.graph');
    grs.each( function(gr) {
        gr.addEvent('click', function() {
            ajxcontent(gr.id);
        });
    });
 }

 function addTreeEvents(gid) {
    // Select clickable imgs in the grid
    $$('#'+gid+' img.plsmns').each( function(e, i) {
        var td  = e.getParent();
        var elt = td.getParent();
        var pid = elt.id;

        // Add click event to each img
        e.addEvent('click', function() {
            // Set parent class to expand (on or off)
            if( elt.hasClass('expand')) {
                elt.removeClass('expand');
                elt.addClass('collapse');
            } else {
                elt.removeClass('collapse');
                elt.addClass('expand');
            }

            // swap image
            var image = $('img_'+pid);
            var imgsrc = (elt.hasClass('collapse'))?'images/tg_expand_closed.png':'images/tg_expand_opened.png';
            image.setProperty('src', imgsrc);
            if(! elt.hasClass('expand') ) {
                // Find kids with this parent id
                var kids = $(gid).getElements('tr[id^='+pid+'-]');
                kids.each( function(kid) {
                    // Hide row by toggling class
                    kid.addClass('close');
                });
            } else {
                doExpand(elt.id, gid);
            }
        });
    });
 }

 function mkTree(gid) {
        var out = $(gid);
        out.addClass('ajax-loading');
        var url = "ajax_backend/treegridServer.php";
        new Request.HTML({
            url: url,
            evalScripts: true,
            method: 'get',
            data: 'id=0', 
            update: out,
            onComplete: function() {
                addTreeEvents('customtree');
                addSpanEvents('customtree');
		        out.removeClass('ajax-loading');
            }
        }).send();
 }

 function mkBrowse(tid) {
        var out = $(tid);
        var url = 'browseServer.php';
        new Request.HTML({
            url: url,
            evalScripts: true,
            method: 'post',
            update: out,
            onComplete: function() {
                addTreeEvents(tid);
            }
        }).send();
 }

 function deleteRow(id) {
    findParent(id).remove();
 }

 // Save treestate to php session
 function saveTreeState(treeid) {
        var qrystr = "act=savetree&treename="+treeid;
        var trs  = $(treeid).getElements('tr');
        trs.each( function(tr) {
            var tmp = "collapse";
            if( tr.hasClass('expand')) tmp = "expand";
            if( tr.hasClass('close')) {
                tmp = tmp+" close";
            } else {
                tmp = tmp+" open";
            }
            qrystr = qrystr+"&tr["+tr.getProperty('id')+"]="+tmp;
        });
        var url = "ajax_backend/prb_be.php";
        new Request.HTML({
            async: false,
            url: url,
            method: 'get',
            evalScripts: true,
            data: qrystr
        }).send();
 }

  // Togglers for host views
 function addTogglers(tgl) {
    var myarray = $$('.'+tgl);
    if (myarray.length) {
       myarray.each(function(toggler, i) {
          var mySlider = new Fx.Slide('C_'+i);
          toggler.addEvent("click", function(e) {
              e = new Event(e);
              mySlider.toggle();
              e.stop();
              // Toggle the image
              var img = ( $('I_'+i).getProperty('src') == 'images/tg_minus.png' )
                        ? 'images/tg_plus.png'
                        : 'images/tg_minus.png';
              $('I_'+i).setProperty('src', img);
          });
       });
    }
 }
