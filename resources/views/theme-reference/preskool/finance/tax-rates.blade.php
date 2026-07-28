<?php $page = 'tax-rates'; ?>
@extends('layout.mainlayout')
@section('content')
   
		<!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content bg-white">
				<div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
					<div class="my-auto mb-2">
						<h3 class="page-title mb-1">Financial Settings</h3>
                     	<nav>
                       		<ol class="breadcrumb mb-0">
                          		<li class="breadcrumb-item">
                            		<a href="{{url('index')}}">Dashboard</a>
                          		</li>
                          		<li class="breadcrumb-item">
                            		<a href="javascript:void(0);">Settings</a>
                          		</li>
                          		<li class="breadcrumb-item active" aria-current="page">Financial Settings</li>
                        	</ol>
                      	</nav>
                    </div>
                    <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                      	<div class="pe-1 mb-2">
                       		<a href="#" class="btn btn-outline-light bg-white btn-icon" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
                         		<i class="ti ti-refresh"></i>
                        	</a>
                        </div>
                    </div>
                </div>
				<div class="row">
					<div class="col-xxl-2 col-xl-3">
						<div class="pt-3 d-flex flex-column list-group mb-4">
							<a href="{{url('payment-gateways')}}" class="d-block rounded p-2">Payment Gateway</a>
							<a href="{{url('tax-rates')}}" class="d-block rounded active p-2">Tax Rates</a>
						</div>
					</div>
					<div class="col-xxl-10 col-xl-9">
						<div class="border-start ps-3">
							<form action="{{url('tax-rates')}}">
								<div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
									<div class="mb-3">
										<h5 class="mb-1">Tax Rates</h5>
										<p>Tax Rates Configuration</p>
									</div>
									<div class="mb-3">
										<a href="#" class="btn btn-outline-light bg-white btn-icon me-2" data-bs-toggle="modal" data-bs-target="#add_tax_rate"><i class="ti ti-plus"></i></a>
										<button class="btn btn-light me-2" type="button">Cancel</button>
										<button class="btn btn-primary" type="submit">Save</button>
									</div>
								</div>
								<div class="d-md-flex">
									<div class="flex-fill">
										<div class="row">
											<div class="col-xxl-4 col-lg-6">
												<div class="mb-3">
													<label class="form-label">VAT</label>
													<div class="position-relative">
														<input type="text" class="form-control pe-5" value="16%">
														<div class="d-flex align-items-center position-absolute end-0 top-0 me-2 mt-2">
															<a href="#" data-bs-toggle="modal" data-bs-target="#edit_tax_rate"><i class="ti ti-edit me-2"></i></a>
															<a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash"></i></a>
														</div>
													</div>
												</div>
											</div>
											<div class="col-xxl-4 col-lg-6">
												<div class="mb-3">
													<label class="form-label">GST</label>
													<div class="position-relative">
														<input type="text" class="form-control" value="14%">
														<div class="d-flex align-items-center position-absolute end-0 top-0 me-2 mt-2">
															<a href="#" data-bs-toggle="modal" data-bs-target="#edit_tax_rate"><i class="ti ti-edit me-2"></i></a>
															<a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash"></i></a>
														</div>
													</div>
												</div>
											</div>
											<div class="col-xxl-4 col-lg-6">
												<div class="mb-3">
													<label class="form-label">HST</label>
													<div class="position-relative">
														<input type="text" class="form-control" value="12%">
														<div class="d-flex align-items-center position-absolute end-0 top-0 me-2 mt-2">
															<a href="#" data-bs-toggle="modal" data-bs-target="#edit_tax_rate"><i class="ti ti-edit me-2"></i></a>
															<a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash"></i></a>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>														
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- /Page Wrapper -->


    @component('components.modal-popup')        
    @endcomponent
@endsection
