<?php $page = 'ui-clipboard'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper cardhead">
    <div class="content">
    
        @component('components.breadcrumb')
        @slot('title')
      Clipboard
        @endslot
    @endcomponent	
        
        <!-- start row -->
        <div class="row">

            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Copy from input</h5>
                    </div>
                    <div class="card-body pb-3">
                        <div class="clipboard">
                            <form class="form-horizontal">
                                <input type="text" class="form-control mb-3" id="input-copy" value="http://www.admin-dashboard.com">
                                <div class="d-flex align-items-center gap-1">
                                    <a class="btn clip-btn btn-primary" href="javascript:void(0);" data-clipboard-action="copy" data-clipboard-target="#input-copy"><i class="far fa-copy me-1"></i> Copy from Input</a>
                                    <a class="btn clip-btn btn-dark" href="javascript:void(0);" data-clipboard-action="cut" data-clipboard-target="#input-copy"><i class="fas fa-cut me-1"></i> Cut from Input</a>
                                </div>
                            </form>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Copy from Text Area</h5>
                    </div>
                    <div class="card-body pb-3">
                        <div class="clipboard">
                            <form class="form-horizontal">
                                <textarea class="form-control mb-3" rows="3" id="textarea-copy">Lorem ipsum dolor sit amet, consectetur adipiscing elit...</textarea>
                                <div class="d-flex align-items-center gap-1">
                                    <a class="btn clip-btn btn-primary" href="javascript:void(0);" data-clipboard-action="copy" data-clipboard-target="#textarea-copy"><i class="far fa-copy me-1"></i> Copy from Input</a>
                                    <a class="btn clip-btn btn-dark" href="javascript:void(0);" data-clipboard-action="cut" data-clipboard-target="#textarea-copy"><i class="fas fa-cut me-1"></i> Cut from Input</a>
                                </div>
                            </form>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Copy Text from Paragraph</h5>
                    </div>
                    <div class="card-body pb-3">
                        <div class="clipboard copy-txt">
                            <p class="otp-pass">Here is your OTP <span id="paragraph-copy1">22991</span>.</p>
                            <p class="mb-3">Please do not share it to anyone</p>
                            <a class="btn clip-btn btn-primary" href="javascript:void(0);" data-clipboard-action="copy" data-clipboard-target="#paragraph-copy1"><i class="far fa-copy me-1"></i> Copy from Input</a>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Copy Hidden Text (Advanced)</h5>
                    </div>
                    <div class="card-body pb-3">
                        <div class="clipboard copy-txt">
                            <p class="mb-3">Link -&gt; <span id="advanced-paragraph">http://www.example.com/example</span></p>
                            <a class="mb-1 btn clip-btn btn-primary" href="javascript:void(0);" data-clipboard-action="copy" data-clipboard-target="#advanced-paragraph"><i class="far fa-copy me-1"></i> Copy Link</a>
                            <a class="mb-1 btn clip-btn btn-warning" href="javascript:void(0);" data-clipboard-action="copy" data-clipboard-text="2291"><i class="far fa-copy me-1"></i> Copy Hidden Code</a>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->

            </div> <!-- end col -->

        </div>
        <!-- end row -->
    
    </div>
</div>
@endsection
