<?php $page = 'form-mask'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper cardhead">
        <div class="content">

            @component('components.breadcrumb')
                @slot('title')
                    Form Mask
                @endslot
            @endcomponent

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Form Mask</h5>
                            <p class="sub-header">Input masks can be used to force the user to enter data conform a specific
                                format. Unlike validation, the user can't enter any other key than the ones specified by the
                                mask.</p>
                        </div>
                        <div class="card-body">
                            <form action="#">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" id="phone" class="form-control">
                                        <span class="form-text text-muted">(999) 999-9999</span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date</label>
                                        <input type="text" id="date" class="form-control">
                                        <span class="form-text text-muted">dd/mm/yyyy</span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SSN field 1</label>
                                        <input type="text" id="ssn" class="form-control">
                                        <span class="form-text text-muted">e.g "999-99-9999"</span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone field + ext.</label>
                                        <input type="text" id="phoneExt" class="form-control">
                                        <span class="form-text text-muted">+40 999 999 999</span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Product Key</label>
                                        <input type="text" id="products" class="form-control">
                                        <span class="form-text text-muted">e.g a*-999-a999</span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Currency</label>
                                        <input type="text" id="currency" class="form-control">
                                        <span class="form-text text-muted">$ 999,999,999.99</span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Eye Script</label>
                                        <input type="text" id="eyescript" class="form-control">
                                        <span class="form-text text-muted">~9.99 ~9.99 999</span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Percent</label>
                                        <input type="text" id="pct" class="form-control">
                                        <span class="form-text text-muted">e.g "99%"</span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Credit Card Number</label>
                                        <input type="text" class="form-control" id="ccn">
                                        <span class="form-text text-muted">e.g "999.999.999.9999"</span>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
