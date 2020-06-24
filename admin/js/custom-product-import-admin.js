(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
		
	$(document).ready(function(){
		FilePond.registerPlugin(FilePondPluginFileValidateType);

		const csvFile = FilePond.create(
			document.querySelector('input.mycsvfile'),{
				// allowFileTypeValidation:false,
				acceptedFileTypes: ['csv'],    
				fileValidateTypeDetectType: (source, type) => new Promise((resolve, reject) => {        
					console.log(type);
			        resolve(type);
			    })
			}
		);
		csvFile.setOptions({
			server:wpadmin.ajax_url+'?action=upload_custom_csv&group_name='+$('#field_groups').val()
		})

	    $('.mycsvfile').on('FilePond:processfile', function(e) {
	    	let fileUrl = e.detail.file.serverId;
	    	if(fileUrl !=""){
	    		$('.mycsvfile, .field_groups').addClass('hide');
	    		$('#group_name').val($('#field_groups').val());
				$.ajax({
				    url: wpadmin.ajax_url,
				    type:'post',
				    data: 'action=parse_custom_csv&url='+fileUrl+'&group_name='+$('#field_groups').val(),
				    success: function(response){
				    	$(".columns_mapping").removeClass('hide');		    	
				    	let res = JSON.parse(response);
				    	$("#uploaded_url").val(res['fileurl']);
				    	let acffields = res['customfields'];
				    	let csvcolumns = res['filecolumns'];
						for (let [key, value] of Object.entries(acffields)) {
						  let html  = '<tr><td><span>'+value+'</span></td><td><select name="'+key+'"><option value="">Please select</option>';
						  csvcolumns.forEach(function(col){
						  	html += '<option value="'+col+'">'+col + '</option>';
						  });
						  html += '</select>';

						  $("#columns_mapping_table").append(html);
						}
				    }
				})
	    	}else{
	    		alert('sorry something went wrong, please upload file again')
	    	}
	    });			

	    $("#submit_mapping").on('click', function(e){
	    	e.preventDefault();
	    	$("#loadingSvg").removeClass('hide');
    		$.ajax({
			    url: wpadmin.ajax_url,
			    type:"POST",
			    dataType:'json',
			    data: $("#mapping_form").serialize()+'&action=update_product_fields',
			    success:function(response){
			    	console.log(response);
			    	$("#loadingSvg").addClass('hide');
			    }
    		})
	    })

		const metaCsvFile = FilePond.create(
			document.querySelector('input.mymetacsvfile'),{
				// allowFileTypeValidation:false,
				acceptedFileTypes: ['csv'],    
				fileValidateTypeDetectType: (source, type) => new Promise((resolve, reject) => {        
					console.log(type);
			        resolve(type);
			    })
			}
		);

		metaCsvFile.setOptions({
			server:wpadmin.ajax_url+'?action=upload_custom_csv'
		});

	    $('.mymetacsvfile').on('FilePond:processfile', function(e) {
	    	let fileUrl = e.detail.file.serverId;
	    	if(fileUrl !=""){
	    		$('.mymetacsvfile, .field_groups').addClass('hide');
				$.ajax({
				    url: wpadmin.ajax_url,
				    type:'post',
				    data: 'action=parse_meta_csv&url='+fileUrl,
				    success: function(response){
				    	$(".columns_mapping").removeClass('hide');		    	
				    	let res = JSON.parse(response);
				    	$("#uploaded_url").val(res['fileurl']);
				    	let csvcolumns = res['filecolumns'];

						  let html  = '<tr><td> Category name </td><td><select name="catname"><option value="">Please select</option>';
						  csvcolumns.forEach(function(col){
						  	html += '<option value="'+col+'">'+col + '</option>';
						  });
						  html += '</select>';

						  html += '<tr><td> Description column </td><td><select name="metacolumn"><option value="">Please select</option>';
						  csvcolumns.forEach(function(col){
						  	html += '<option value="'+col+'">'+col + '</option>';
						  });
						  html += '</select>';

						  $("#columns_mapping_table").append(html);
				    }
				})
	    	}else{
	    		alert('sorry something went wrong, please upload file again')
	    	}
	    });			

    $("#import_metadescription").on('click', function(e){
    	e.preventDefault();
    	$("#loadingSvg").removeClass('hide');
		$.ajax({
		    url: wpadmin.ajax_url,
		    type:"POST",
		    dataType:'json',
		    data: $("#mapping_form").serialize()+'&action=update_termsdescription',
		    success:function(response){
		    	console.log(response);
		    	$("#loadingSvg").addClass('hide');
		    }
		})
    })    

    $("#import_newproducts").on('click', function(e){    	
    	e.preventDefault();
    	let fileUrl = $("#uploaded_url").val();
    	$("#loadingSvg").removeClass('hide');
    	$("#import_progress").removeClass('hide');
		$.ajax({
		    url: wpadmin.ajax_url,
		    type:'post',
		    data: $("#mapping_form").serialize()+'&action=new_import_products',
		    success: function(response){		    	
				let res = JSON.parse(response);
		    	$("#currentpage").val(res['nextpage']);
		    	$("#importedrows").val(res['lastitem']);
		    	$('#importedspan').html(res['lastitem']);
		    	if(parseInt($("#totalnumber").val())<=parseInt($("#importedrows").val())){
					$("#loadingSvg").addClass('hide');		    		
		    	}else{
		    		$("#import_newproducts").trigger('click');
		    	}
		    }
		})
    })  
	// $("#import_galleryimages").on('click', function(e){
 //    	e.preventDefault();
 //    	$("#loadingSvg1").removeClass('hide');
	// 	$.ajax({
	// 	    url: wpadmin.ajax_url,
	// 	    type:'post',
	// 	    data: $("#mapping_form").serialize()+'&action=import_galleries',
	// 	    success: function(response){
	// 	    	$("#loadingSvg1").addClass('hide');
	// 	    	console.log(response);
	// 	    }
	// 	})
	// })
    $("#export_products_btn").on('click', function(e){
    	e.preventDefault();
    	$("#loadingSvg").removeClass('hide');
		$.ajax({
		    url: wpadmin.ajax_url,
		    type:"POST",
		    data: $("#mapping_form").serialize()+'&action=cpiexport_products',
		    success:function(response){
		    	$("#loadingSvg").addClass('hide');
		    	window.location = response;
		    }
		})
    });

    // New Products
    const newProductsCsvFile = FilePond.create(
		document.querySelector('input.newproductscsvfile'),{
			// allowFileTypeValidation:false,
			acceptedFileTypes: ['csv'],    
			fileValidateTypeDetectType: (source, type) => new Promise((resolve, reject) => {        
				console.log(type);
		        resolve(type);
		    })
		}
	);

	newProductsCsvFile.setOptions({
		server:wpadmin.ajax_url+'?action=upload_custom_csv'
	});

    $('.newproductscsvfile').on('FilePond:processfile', function(e) {
    	let fileUrl = e.detail.file.serverId;
    	if(fileUrl !=""){
    		$('.newproductscsvfile, .field_groups').addClass('hide');
    		$("#uploaded_url").val(fileUrl);
			$.ajax({
			    url: wpadmin.ajax_url,
			    type:'post',
			    data: 'action=parse_newproducts_csv&url='+fileUrl+'&productType='+$("#product_type_select").val(),
			    success: function(response){

			    	$(".columns_mapping").removeClass('hide');		    	
			    	let res = JSON.parse(response);
			    
			    	let csvcolumns = res['filecolumns'];
			    	let productFields = res['productFields'];
					$("#totalnumber").val(res['noofrows']);
					$("#totalrowspan").html(parseInt(res['noofrows']));
			    	let html = '';
			    	productFields.forEach(function(fed){
			    		html += '<tr><td> '+fed+' </td><td><select name="'+fed+'"><option value="">Please select</option>';
					  	csvcolumns.forEach(function(col){
						  	html += '<option value="'+col+'">'+col + '</option>';
						});
						html += '</select></td></tr>';
			    	})				  					

					$("#columns_mapping_table").append(html);
			    }
			})
    	}else{
    		alert('sorry something went wrong, please upload file again')
    	}
    });	
})
})( jQuery );
