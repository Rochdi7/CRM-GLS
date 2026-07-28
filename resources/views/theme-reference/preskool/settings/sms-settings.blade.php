<?php $page = 'sms-settings'; ?>
@extends('layout.mainlayout')
@section('content')
 <!-- Page Wrapper -->
 <div class="page-wrapper">
	<div class="content bg-white">
		<div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
			<div class="my-auto mb-2">
				<h3 class="page-title mb-1">System Settings</h3>
				 <nav>
					   <ol class="breadcrumb mb-0">
						  <li class="breadcrumb-item">
							<a href="{{url('index')}}">Dashboard</a>
						  </li>
						  <li class="breadcrumb-item">
							<a href="javascript:void(0);">Settings</a>
						  </li>
						  <li class="breadcrumb-item active" aria-current="page">System Settings</li>
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
					<a href="{{url('email-settings')}}" class="d-block rounded p-2">Email Settings</a>
					<a href="{{url('email-templates')}}" class="d-block rounded p-2">Email Templates</a>
					<a href="{{url('sms-settings')}}" class="d-block rounded active p-2">SMS Settings</a>
					<a href="{{url('otp-settings')}}" class="d-block rounded p-2">OTP</a>
					<a href="{{url('gdpr-cookies')}}" class="d-block rounded p-2">GDPR Cookies</a>
				</div>
			</div>
			<div class="col-xxl-10 col-xl-9">
				<div class="flex-fill border-start ps-3">
					<div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
						<div class="mb-3">
							<h5 class="mb-1">SMS Settings</h5>
							<p>SMS Settings Configuration</p>
						</div>
					</div>
					<div class="card">
						<div class="card-body p-3 pb-0">
							<div class="row">
								<div class="col-xxl-4 col-md-6">
									<div class="d-flex align-items-center justify-content-between bg-white p-3 border rounded mb-3">
										<span class="d-block"><img src="{{URL::asset('build/img/icons/sms-icon-01.svg')}}" alt="Img"></span>
										<div class="d-flex align-items-center">
											<div class="status-toggle modal-status">
												<input type="checkbox" id="user1" class="check">
												<label for="user1" class="checktoggle"> </label>
											</div>
											<a href="#" class="btn btn-outline-light bg-white btn-icon ms-2" data-bs-toggle="modal" data-bs-target="#connect_sms"><i class="ti ti-settings-cog"></i></a>
										</div>
									</div>
								</div>
								<div class="col-xxl-4 col-md-6">
									<div class="d-flex align-items-center justify-content-between bg-white p-3 border rounded mb-3">
										<span class="d-block"><img src="{{URL::asset('build/img/icons/sms-icon-02.svg')}}" alt="Img"></span>
										<div class="d-flex align-items-center">
											<div class="status-toggle modal-status">
												<input type="checkbox" id="user2" class="check">
												<label for="user2" class="checktoggle"> </label>
											</div>
											<a href="#" class="btn btn-outline-light bg-white btn-icon ms-2" data-bs-toggle="modal" data-bs-target="#connect_sms"><i class="ti ti-settings-cog"></i></a>
										</div>
									</div>
								</div>
								<div class="col-xxl-4 col-md-6">
									<div class="d-flex align-items-center justify-content-between bg-white p-3 border rounded mb-3">
										<span class="d-block"><img src="{{URL::asset('build/img/icons/sms-icon-03.svg')}}" alt="Img"></span>
										<div class="d-flex align-items-center">
											<div class="status-toggle modal-status">
												<input type="checkbox" id="user3" class="check">
												<label for="user3" class="checktoggle"> </label>
											</div>
											<a href="#" class="btn btn-outline-light bg-white btn-icon ms-2" data-bs-toggle="modal" data-bs-target="#connect_sms"><i class="ti ti-settings-cog"></i></a>
										</div>
									</div>
								</div>
							</div>									
						</div>
					</div>	
				</div>
			</div>
		</div>
	</div>
</div>
<!-- /Page Wrapper -->

    @component('components.modal-popup')        
    @endcomponent
@endsection
