<?php $page = 'form-input-groups'; ?>
@extends('layout.mainlayout')
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper cardhead">
    <div class="content">
    
        <!-- Page Header -->
        <div class="page-header">
            <h3 class="page-title my-auto">Input Groups</h3>
            <div>
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                  <a href="javascript:void(0)">UI Elements</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Input Groups</li>
              </ol>
            </div>
          </div>
        <!-- /Page Header -->
        
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Basic Examples</h5>
                    </div>
                    <div class="card-body">
                        <form action="#">
                            <div class="mb-3 row">
                                <label class="form-label col-lg-2">Group Left</label>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <span class="input-group-text" id="basic-addon1">@</span>
                                        <input type="text" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="form-label col-lg-2">Group Right</label>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Recipient's username" aria-label="Recipient's username" aria-describedby="basic-addon2">
                                        <span class="input-group-text" id="basic-addon2">@example.com</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="form-label col-lg-2">URL Example</label>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <span class="input-group-text" id="basic-addon3">https://example.com/users/</span>
                                        <input type="text" class="form-control" id="basic-url" aria-describedby="basic-addon3">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="form-label col-lg-2">Group with Price</label>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control" aria-label="Amount (to the nearest dollar)">
                                        <span class="input-group-text">.00</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 row mb-0">
                                <label class="form-label col-lg-2">Group with Price (Left)</label>
                                <div class="col-lg-10">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">$</span>
                                        <span class="input-group-text">0.00</span>
                                        <input type="text" class="form-control">
                                    </div>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control" aria-label="Amount (to the nearest dollar)">
                                        <span class="input-group-text">.00</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 row mb-0">
                                <label class="form-label col-lg-2">With textarea</label>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <span class="input-group-text">With textarea</span>
                                        <textarea class="form-control" aria-label="With textarea"></textarea>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Sizing</h5>
                    </div>
                    <div class="card-body">
                        <form action="#">
                            <div class="mb-3 row">
                                <label class="form-label col-lg-2">Input Group Large</label>
                                <div class="col-lg-10">
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text" id="inputGroup-sizing-lg">Large</span>
                                        <input type="text" class="form-control" aria-label="Sizing example input" aria-describedby="inputGroup-sizing-lg">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="form-label col-lg-2">Input Group Default</label>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <span class="input-group-text" id="inputGroup-sizing-default">Default</span>
                                          <input type="text" class="form-control" aria-label="Sizing example input" aria-describedby="inputGroup-sizing-default">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 row mb-0">
                                <label class="form-label col-lg-2">Input Group Small</label>
                                <div class="col-lg-10">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text" id="inputGroup-sizing-sm">Small</span>
                                          <input type="text" class="form-control" aria-label="Sizing example input" aria-describedby="inputGroup-sizing-sm">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Checkbox and Radio Addons</h5>
                    </div>
                    <div class="card-body">
                        <form action="#">
                            <div class="mb-3 row">
                                <label class="form-label col-lg-2">Checkbox</label>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <input class="form-check-input" type="checkbox" value="" aria-label="Checkbox for following text input">
                                        </div>
                                        <input type="text" class="form-control" aria-label="Text input with checkbox">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 row mb-0">
                                <label class="form-label col-lg-2">Radio</label>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <input class="form-check-input" type="radio" value="" aria-label="Radio button for following text input">
                                        </div>
                                        <input type="text" class="form-control" aria-label="Text input with radio button">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Multiple Addons</h5>
                    </div>
                    <div class="card-body">
                        <form action="#">
                            <div class="mb-3 row">
                                <label class="form-label col-lg-2">Radio and Text Addons</label>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <input type="checkbox">
                                        </span>
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 row mb-0">
                                <label class="form-label col-lg-2">Two Addons</label>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <span class="input-group-text">0.00</span>
                                        <input type="text" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Buttons with dropdowns</h5>
                    </div>
                    <div class="card-body">
                        <form action="#">
                            <div class="mb-3 row">
                                <label class="form-label col-lg-2">Left Dropdown Text Addons</label>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                        </ul>
                                        <input type="text" class="form-control" aria-label="Text input with dropdown button">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 row mb-0">
                                <label class="form-label col-lg-2">Right Dropdown Text Addons</label>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <input type="text" class="form-control" aria-label="Text input with dropdown button">
                                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 row mb-0">
                                <label class="form-label col-lg-2">Right Dropdown Text Addons</label>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <button class="btn btn-soft-primary dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">Dropdown</button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="javascript:void(0);">Action before</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);">Another action before</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                        </ul>
                                        <input type="text" class="form-control"
                                            aria-label="Text input with 2 dropdown buttons">
                                        <button class="btn btn-soft-primary dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">Dropdown</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="javascript:void(0);">Action</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);">Another action</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);">Something else here</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li><a class="dropdown-item" href="javascript:void(0);">Separated link</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    
    </div>	
</div>
<!-- /Page Wrapper -->

@endsection
