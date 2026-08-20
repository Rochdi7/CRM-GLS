<?php $page = 'payment-gateways'; ?>
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
							<a href="{{url('payment-gateways')}}" class="d-block rounded p-2 active">Payment Gateway</a>
							<a href="{{url('tax-rates')}}" class="d-block rounded p-2">Tax Rates</a>
						</div>
					</div>
					<div class="col-xxl-10 col-xl-9">
						<div class="border-start ps-3">
							<form action="{{url('payment-gateways')}}">
								<div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
									<div class="mb-3">
										<h5 class="mb-1">Payment Gateways</h5>
										<p>Payments Settings Configuration</p>
									</div>
									<div class="mb-3">
										<a href="{{url('email')}}" class="btn btn-outline-light bg-white btn-icon me-2"><i class="ti ti-mail-share"></i></a>
										<button class="btn btn-light me-2" type="button">Cancel</button>
										<button class="btn btn-primary" type="submit">Save</button>
									</div>
								</div>
								<div class="row">
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-01.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-success text-success me-2">Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user1" class="check" checked>
														<label for="user1" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>PayPal is the faster, safer way to send and receive money or make an online payment.</p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>View Integration</a>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-02.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-dark text-dark me-2">Not Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user2" class="check">
														<label for="user2" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>APIs to accept credit cards, manage subscriptions, send money.</p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>Connect Now</a>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-03.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-dark text-dark me-2">Not Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user3" class="check">
														<label for="user3" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>Braintree offers more fraud protection and security features.</p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>Connect Now</a>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-04.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-dark text-dark me-2">Not Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user4" class="check">
														<label for="user4" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>Works stably and reliably and features are valuable and necessary for any software.</p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>Connect Now</a>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-05.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-success text-success me-2">Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user5" class="check" checked>
														<label for="user5" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>Razorpay is a comprehensive payment gateway and financial solutions provider in India.</p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>View Integration</a>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-06.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-dark text-dark me-2">Not Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user6" class="check">
														<label for="user6" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>Allows  send international money transfers and payments quickly with low fees.</p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>Connect Now</a>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-07.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-dark text-dark me-2">Not Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user7" class="check">
														<label for="user7" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>Replaces your physical cards and cash with an easier, safer, more private and secure </p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>Connect Now</a>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-08.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-dark text-dark me-2">Not Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user8" class="check">
														<label for="user8" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>Fast, Low-Cost Solution for your International Business.</p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>Connect Now</a>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-09.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-success text-success me-2">Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user9" class="check" checked>
														<label for="user9" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>Provide payment solution to individuals to make payments using credit card.</p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>View Integration</a>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-10.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-dark text-dark me-2">Not Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user10" class="check">
														<label for="user10" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>Paytm stands for Pay through mobile and it is India's largest mobile payments.</p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>Connect Now</a>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-11.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-success text-success me-2">Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user11" class="check" checked>
														<label for="user11" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>Online payment platform that enables to send and receive money via emails.</p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>View Integration</a>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-12.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-dark text-dark me-2">Not Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user12" class="check">
														<label for="user12" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>Midtrans provides the maximum number of payment methods across payment gateways.</p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>Connect Now</a>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-13.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-success text-success me-2">Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user13" class="check" checked>
														<label for="user13" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>PyTorch is  a network through which your customers transfer funds to you.</p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>View Integration</a>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-14.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-dark text-dark me-2">Not Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user14" class="check">
														<label for="user14" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>Direct transfer of funds from one bank account into another.</p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>Connect Now</a>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6 d-flex">
										<div class="card flex-fill">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<span class="d-inline-flex align-items-center justify-content-center border rounded p-2"><img src="{{URL::asset('build/img/payment-gateway/payment-gateway-15.svg')}}" alt="Img"></span>
												<div class="d-flex align-items-center">
													<span class="badge bg-transparent-dark text-dark me-2">Not Connected</span>
													<div class="status-toggle modal-status">
														<input type="checkbox" id="user15" class="check">
														<label for="user15" class="checktoggle"> </label>
													</div>
												</div>
											</div>
											<div class="card-body pt-0">
												<p>Indicating that goods must be paid for at the time of delivery. </p>
											</div>
											<div class="card-footer">
												<a href="#" class="btn btn-outline-light d-flex justify-content-center align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#connect_payment"><i class="ti ti-tool me-2"></i>Connect Now</a>
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
