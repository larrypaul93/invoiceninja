@extends('public.header')

@section('head')
	@parent
    <script src="{{ asset('pdf.built.js') }}?no_cache={{ NINJA_VERSION }}" type="text/javascript"></script>

@stop    
@section('content')

    
	<div class="container" id="main-container" style="min-height:800px;height: 800px;overflow-y: auto;">

    <div id="paymentButtons"  style="text-align:right">
                {!! Button::normal('Print PDF')
                        ->withAttributes(['onclick' => 'printPDF()', 'id' => 'printPdfButton'])
                        ->appendIcon(Icon::create('print'))->large() !!}&nbsp;&nbsp; 
                <script type="text/javascript">
                    function printPDF(){
                        $("body").addClass("pdf-print");
                        window.print();
                        $("body").removeClass("pdf-print");
                    }
                </script>  
                {!! Button::normal(trans('texts.download_pdf'))->withAttributes(['onclick' => 'onDownloadClick()'])->large() !!}&nbsp;&nbsp;      
            
    </div>
    <div id="pdfCanvas" style="display:none;width:100%;background-color:#525659;border:solid 2px #9a9a9a;padding-top:40px;text-align:center">
        <canvas id="theCanvas" style="max-width:100%;border:solid 1px #CCCCCC;"></canvas>
    </div>

	<p>&nbsp;</p>
    <script>
        PDFJS.workerSrc = '{{ asset('js/pdf_viewer.worker.js') }}';
   
       var pdfAsArray = "/client/report/pdf/{{$report_no}}";
       PDFJS.getDocument(pdfAsArray).then(function getPdfHelloWorld(pdf) {
          var wrapper = document.getElementById('pdfCanvas');
          wrapper.innerHTML = '';
        for(var i = 1; i <= pdf.numPages; i++ ){
            pdf.getPage(i).then(function getPageHelloWorld(page) {
                var scale = 1.5;
                var viewport = page.getViewport(scale);
                var canvas = document.createElement("canvas");
                wrapper.appendChild(canvas);
                canvas.style.cssText = "max-width:100%;border:solid 1px #CCCCCC;";
                //var canvas = document.getElementById('theCanvas');
                var context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                page.render({canvasContext: context, viewport: viewport});
                $('#pdfObject').hide();
                $('#pdfCanvas').show();
                    
            });
        }

      });
      function onDownloadClick() {
           PDFJS.getDocument(pdfAsArray).then(function getPdfHelloWorld(pdf) {
               doc.save('service-report-{{$report_no}}.pdf');
           });
				
	  }

    </script>
@stop

