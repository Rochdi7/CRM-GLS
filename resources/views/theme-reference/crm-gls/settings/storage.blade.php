<?php $page = 'storage'; ?>
@extends('layout.mainlayout')
@section('content')
   	
<!-- Page Wrapper -->
<div class="page-wrapper">
	<div class="content bg-white">
		<div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
			<div class="my-auto mb-2">
				<h3 class="page-title mb-1">Other Settings</h3>
				 <nav>
					   <ol class="breadcrumb mb-0">
						  <li class="breadcrumb-item">
							<a href="{{url('index')}}">Dashboard</a>
						  </li>
						  <li class="breadcrumb-item">
							<a href="javascript:void(0);">Settings</a>
						  </li>
						  <li class="breadcrumb-item active" aria-current="page">Other Settings</li>
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
					<a href="{{url('storage')}}" class="d-block rounded active p-2">Storage</a>
					<a href="{{url('ban-ip-address')}}" class="d-block rounded p-2">Ban IP Address</a>
				</div>
			</div>
			<div class="col-xxl-10 col-xl-9">
				<div class="border-start ps-3">
					<form action="{{url('storage')}}">
						<div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
							<div class="mb-3">
								<h5 class="mb-1">Storage</h5>
								<p>Storage Settings Configuration</p>
							</div>
							<div class="mb-3">
								<button class="btn btn-light me-2" type="button">Cancel</button>
								<button class="btn btn-primary" type="submit">Save</button>
							</div>
						</div>
						<div class="card">
							<div class="card-body p-3 pb-0">
								<div class="row">
									<div class="col-xxl-4 col-md-6">
										<div class="d-flex align-items-center justify-content-between bg-white p-3 border rounded mb-3">
											<div class="d-flex align-items-center">
												<span class="avatar avatar-md rounded bg-light p-2 me-2"><img src="{{URL::asset('build/img/icons/storage-icon-01.svg')}}" alt="Img"></span>
												<h6>Local Storage</h6>
											</div>
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
											<div class="d-flex align-items-center">
												<span class="avatar avatar-md rounded bg-light p-2 me-2"><img src="{{URL::asset('build/img/icons/storage-icon-02.svg')}}" alt="Img"></span>
												<h6>AWS </h6>
											</div>
											<div class="d-flex align-items-center">
												<div class="status-toggle modal-status">
													<input type="checkbox" id="user2" class="check">
													<label for="user2" class="checktoggle"> </label>
												</div>
												<a href="#" class="btn btn-outline-light bg-white btn-icon ms-2" data-bs-toggle="modal" data-bs-target="#connect_sms"><i class="ti ti-settings-cog"></i></a>
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
