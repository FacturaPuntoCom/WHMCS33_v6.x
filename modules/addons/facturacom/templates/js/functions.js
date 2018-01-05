$(function(){
    initButtons();

    function initButtons(){

        $('.btn-send-email').unbind().on('click', function(e){
            e.preventDefault();
            $(this).html('<span class="glyphicon glyphicon-refresh"></span> Enviando factura');
            $('.btn-send-email').attr("disabled", true);

            var systemURL = $('#systemURL').val();
            var invoiceId = $(this).attr('data-uid');
            var invoiceVersion = $(this).attr('data-version');
            var parameters = {
                'function' : 'sendInvoice',
                'uid' : invoiceId,
                'version' : invoiceVersion
            };

            $.ajax({
                data:  parameters,
                url:   systemURL + 'modules/addons/facturacom/lib/apihandler.php',
                type:  'post',
                dataType: 'json',
                success:  function (response) {

                    $('.btn-send-email').attr("disabled", false);
                    $('.btn-send-email').html('<span class="glyphicon glyphicon-envelope"></span> Enviar por correo');
                    $('#facturaModal #facturaModalText').html(response.message);
                    $('#facturaModal').modal();
                }
            });

        });

        $('.btn-cancel-invoice').unbind().on('click', function(e){
            e.preventDefault();
            var cancelBtn = $(this);

            //facturaModalConfirm
            $('#facturaModalConfirm').modal({ backdrop: 'static', keyboard: false })
                .one('click', '#cancelInvoiceBtn', function (e) {


                    cancelBtn.html('<span class="glyphicon glyphicon-refresh"></span> Cancelando');
                    $('.btn-cancel-invoice').attr("disabled", true);

                    var systemURL = $('#systemURL').val();
                    var invoiceId = cancelBtn.attr('data-uid');
                    var invoiceVersion = $(this).attr('data-version');
                    var parameters = {
                        'function' : 'cancelInvoice',
                        'uid' : invoiceId,
                        'version' : invoiceVersion
                    };

                    $.ajax({
                        data: parameters,
                        url: systemURL + 'modules/addons/facturacom/lib/apihandler.php',
                        type: 'post',
                        dataType: 'json',
                        success: function(response){
                            loadInvoicesTable();
                            $('.btn-cancel-invoice').attr("disabled", false);
                            $('.btn-cancel-invoice').html('<span class="glyphicon glyphicon-ban-circle"></span> Cancelar');
                            $('#facturaModal #facturaModalText').html(response.message);
                            $('#facturaModal').modal();
                        }
                    });

            });





        });
    }

    function loadInvoicesTable(){
        var systemURL = $('#systemURL').val();
        var parameters = {
            'function' : 'loadInvoicesTable'
        };

        $.ajax({
            data: parameters,
            url: systemURL + 'modules/addons/facturacom/lib/apihandler.php',
            type: 'post',
            dataType: 'json',
            success: function(response){
                var invoices = response.data;
                var index;
                var content = '';

                for (index = 0; index < invoices.length; ++index) {
                    var label = (invoices[index].Status == 'enviada' ? 'label-success' : 'label-danger');

                    content += '<tr><th scope="row">' + (index + 1) + '</th>' +
                        '<td>' + invoices[index].Folio  + '</td>' +
                        '<td>' + invoices[index].FechaTimbrado  + '</td>' +
                        '<td>' + invoices[index].Receptor  + '</td>' +
                        '<td><a href="' + systemURL + 'admin/clientssummary.php?userid=' + invoices[index].ReferenceClient + '" target="_blank">' + invoices[index].ReferenceClient  + '</a></td>' +
                        '<td><a href="' + systemURL + 'admin/orders.php?action=view&id=' + invoices[index].NumOrder + '" target="_blank">' + invoices[index].NumOrder  + '</a></td>' +
                        '<td><span class="label ' + label + '">' + invoices[index].Status  + '</span></td>' +
                        '<td><a href="'+ systemURL + 'modules/addons/facturacom/lib/downloadhandler.php?uid=' + invoices[index].UID  + '&type=pdf" target="_blank">PDF</a></td>' +
                        '<td><a href="'+ systemURL + 'modules/addons/facturacom/lib/downloadhandler.php?uid=' + invoices[index].UID  + '&type=xml" target="_blank">XML</a></td>';

                    if(invoices[index].Status == 'enviada'){
                        content+= '<td><a href="#" class="btn-send-email btn btn-info" data-uid="' + invoices[index].UID + '" data-version="' + invoices[index].Version + '">' +
                                        '<span class="glyphicon glyphicon-envelope"></span> Enviar por correo</a>' +
                                    '<a href="#" class="btn-cancel-invoice btn btn-danger" data-uid="' + invoices[index].UID + '" data-version="' + invoices[index].Version + '">' +
                                        '<span class="glyphicon glyphicon-ban-circle"></span> Cancelar</a></td>';
                    }else{
                        content+= '<td>&nbsp;</td>';
                    }
                    content+= '</tr>';
                }

                $('#adminInvoices tbody').html(content);
                initButtons();
            }
        });
    }

    $('#adminInvoices').DataTable({
      'aoColumnDefs': [
        { 'bSortable': false, 'aTargets': [ 6,7,8,9 ] }
      ],
      'language': {
        'decimal':        '',
        'emptyTable':     'No hay información disponible',
        'info':           'Mostrando del _START_ al _END_ de _TOTAL_ registros',
        'infoEmpty':      'Mostrando del 0 al 0 de 0 registros',
        'infoFiltered':   '(filtrado de _MAX_ registros totales)',
        'infoPostFix':    '',
        'thousands':      ',',
        'lengthMenu':     'Mostrar _MENU_ registros',
        'loadingRecords': 'Cargando...',
        'processing':     'Procesando...',
        'search':         'Buscar:',
        'zeroRecords':    'No se encontraron resultados',
        'paginate': {
          'first':    'Primero',
          'last':     'Último',
          'next':     'Siguiente',
          'previous': 'Anterior'
        },
        'aria': {
          'sortAscending':  ': activar para ordernar la columna ascendentemente',
          'sortDescending': ': activar para ordernar la columna descendentemente'
        }
      }
    });

});
