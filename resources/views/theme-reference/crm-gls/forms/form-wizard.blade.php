<?php $page = 'form-wizard'; ?>
@extends('layout.mainlayout')
@section('content')
 <!-- Page Wrapper -->
 <div class="page-wrapper cardhead">

	<div class="content">

		@component('components.breadcrumb')
		@slot('title')
			Form Wizard
		@endslot
	@endcomponent


		<div class="row">

			<!-- Lightbox -->
			<div class="col-lg-12">
				<div class="card">
					<div class="card-header">
						<h4 class="card-title mb-0">Basic Wizard</h4>
					</div>
					<div class="card-body">
						<div id="basic-pills-wizard" class="twitter-bs-wizard">
							<ul class="nav nav-tabs twitter-bs-wizard-nav">
								<li class="nav-item">
									<a href="javascript:void(0);" class="nav-link border-0" data-bs-toggle="tab" data-bs-target="#seller-details">
										<div class="step-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Seller Details">
											<i class="far fa-user"></i>
										</div>
									</a>
								</li>
								<li class="nav-item">
									<a href="javascript:void(0);" class="nav-link border-0" data-bs-toggle="tab" data-bs-target="#company-document">
										<div class="step-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Company Document">
											<i class="fas fa-map-pin"></i>
										</div>
									</a>
								</li>
								
								<li class="nav-item">
									<a href="javascript:void(0);" class="nav-link border-0" data-bs-toggle="tab" data-bs-target="#bank-detail">
										<div class="step-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Bank Details">
											<i class="fas fa-credit-card"></i>
										</div>
									</a>
								</li>
							</ul>
							<!-- wizard-nav -->

							<div class="tab-content twitter-bs-wizard-tab-content">
								<div class="tab-pane show active" id="seller-details">
									<div class="mb-4">
										<h5>Enter Your Personal Details</h5>
									</div>
									<form>
										<div class="row">
											<div class="col-lg-6">
												<div class="mb-3">
													<label for="basicpill-firstname-input" class="form-label">First name</label>
													<input type="text" class="form-control" id="basicpill-firstname-input">
												</div>
											</div>
											<div class="col-lg-6">
												<div class="mb-3">
													<label for="basicpill-lastname-input" class="form-label">Last name</label>
													<input type="text" class="form-control" id="basicpill-lastname-input">
												</div>
											</div>
										</div>

										<div class="row">
											<div class="col-lg-6">
												<div class="mb-3">
													<label for="basicpill-phoneno-input" class="form-label">Phone</label>
													<input type="text" class="form-control" id="basicpill-phoneno-input">
												</div>
											</div>
											<div class="col-lg-6">
												<div class="mb-3">
													<label for="basicpill-email-input" class="form-label">Email</label>
													<input type="email" class="form-control" id="basicpill-email-input">
												</div>
											</div>
										</div>
									</form>
									<ul class="pager wizard twitter-bs-wizard-pager-link">
										<li class="next"><a href="javascript: void(0);" class="btn btn-primary">Next <i
										class="bx bx-chevron-right ms-1"></i></a></li>
									</ul>
								</div>
								<!-- tab pane -->
								<div class="tab-pane fade" id="company-document">
								  <div>
									<div class="mb-4">
										<h5>Enter Your Address</h5>
									</div>
									<form>
										<div class="row">
											<div class="col-lg-6">
												<div class="mb-3">
													<label for="basicpill-pancard-input" class="form-label">Address 1</label>
													<input type="text" class="form-control" id="basicpill-pancard-input">
												</div>
											</div>

											<div class="col-lg-6">
												<div class="mb-3">
													<label for="basicpill-vatno-input" class="form-label">Address 2</label>
													<input type="text" class="form-control" id="basicpill-vatno-input">
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-lg-6">
												<div class="mb-3">
													<label for="basicpill-cstno-input" class="form-label">Landmark</label>
													<input type="text" class="form-control" id="basicpill-cstno-input">
												</div>
											</div>

											<div class="col-lg-6">
												<div class="mb-3">
													<label for="basicpill-servicetax-input" class="form-label">Town</label>
													<input type="text" class="form-control" id="basicpill-servicetax-input">
												</div>
											</div>
										</div>
									</form>
									<ul class="pager wizard twitter-bs-wizard-pager-link">
										<li class="previous"><a href="javascript: void(0);" class="btn btn-primary" onclick="nextTab()"><i
										class="bx bx-chevron-left me-1"></i> Previous</a></li>
										<li class="next"><a href="javascript: void(0);" class="btn btn-primary">Next <i
										class="bx bx-chevron-right ms-1"></i></a></li>
									</ul>
									</div>
								</div>
								<!-- tab pane -->
								<div class="tab-pane fade" id="bank-detail">
									<div>
										<div class="mb-4">
											<h5>Payment Details</h5>
										</div>
										<form>
											<div class="row">
												<div class="col-lg-6">
													<div class="mb-3">
														<label for="basicpill-namecard-input" class="form-label">Name on Card</label>
														<input type="text" class="form-control" id="basicpill-namecard-input">
													</div>
												</div>

												<div class="col-lg-6">
													<div class="mb-3">
														<label class="form-label">Credit Card Type</label>
														<select class="form-select">
																<option selected>Select Card Type</option>
																<option value="AE">American Express</option>
																<option value="VI">Visa</option>
																<option value="MC">MasterCard</option>
																<option value="DI">Discover</option>
														</select>
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-lg-6">
													<div class="mb-3">
														<label for="basicpill-cardno-input" class="form-label">Credit Card Number</label>
														<input type="text" class="form-control" id="basicpill-cardno-input">
													</div>
												</div>

												<div class="col-lg-6">
													<div class="mb-3">
														<label for="basicpill-card-verification-input" class="form-label">Card Verification Number</label>
														<input type="text" class="form-control" id="basicpill-card-verification-input">
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-lg-6">
													<div class="mb-3">
														<label for="basicpill-expiration-input" class="form-label">Expiration Date</label>
														<input type="text" class="form-control" id="basicpill-expiration-input">
													</div>
												</div>
											</div>
										</form>
										<ul class="pager wizard twitter-bs-wizard-pager-link">
											<li class="previous"><a href="javascript: void(0);" class="btn btn-primary" onclick="nextTab()"><i
														class="bx bx-chevron-left me-1"></i> Previous</a></li>
											<li class="float-end"><a href="{{url('form-wizard')}}" class="btn btn-primary">Save
													Changes</a></li>
										</ul>
									</div>
								</div>
								<!-- tab pane -->
							</div>
							<!-- end tab content -->
						</div>
					</div>
					<!-- end card body -->
				</div>
			</div>
			<!-- /Wizard -->

		</div>

	</div>

</div>
<!-- /Page Wrapper -->
	
@endsection
