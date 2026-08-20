<?php $page = 'social-authentication'; ?>
@extends('layout.mainlayout')
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper">
	<div class="content bg-white">
		<div class="d-md-flex d-block align-items-center justify-content-between border-bottom pb-3">
			<div class="my-auto mb-2">
				<h3 class="page-title mb-1">Website Settings</h3>
				 <nav>
					   <ol class="breadcrumb mb-0">
						  <li class="breadcrumb-item">
							<a href="{{url('index')}}">Dashboard</a>
						  </li>
						  <li class="breadcrumb-item">
							<a href="javascript:void(0);">Settings</a>
						  </li>
						  <li class="breadcrumb-item active" aria-current="page">Website Settings</li>
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
					<a href="{{url('company-settings')}}" class="d-block rounded p-2">Company Settings</a>
					<a href="{{url('localization')}}" class="d-block rounded p-2">Localization</a>
					<a href="{{url('prefixes')}}" class="d-block rounded p-2">Prefixes</a>
					<a href="{{url('preferences')}}" class="d-block rounded p-2">Preferences</a>
					<a href="{{url('social-authentication')}}" class="d-block rounded p-2 active">Social Authentication</a>
					<a href="{{url('language')}}" class="d-block rounded p-2">Language</a>
				</div>
			</div>
			<div class="col-xxl-10 col-xl-9">
				<div class="border-start ps-3">
					<form action="{{url('social-authentication')}}">
						<div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
							<div class="mb-3">
								<h5 class="mb-1">Social Authentication</h5>
								<p>Personalize your path and settings of your website</p>
							</div>
							<div class="mb-3">
								<button class="btn btn-light me-2" type="button">Cancel</button>
								<button class="btn btn-primary" type="submit">Save</button>
							</div>
						</div>
						<div class="d-md-flex d-block">
							<div class="flex-fill">
								<div class="row">
									<div class="col-xxl-4 col-xl-6">
										<div class="card">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<div class="d-flex align-items-center">
													<span class="avatar avatar-lg p-2 rounded bg-gray flex-shrink-0 me-2"><img src="{{URL::asset('build/img/icons/fb-icon.svg')}}" alt="Img"></span>
													<h6>Facebook</h6>
												</div>
												<span class="badge bg-transparent-success text-success">Connected</span>
											</div>
											<div class="card-body pt-0">
												<p>Connect with friends, family and other people you know.</p>
											</div>
											<div class="card-footer d-flex justify-content-between align-items-center">
												<div>
													<a href="#" class="btn btn-outline-light"><i class="ti ti-tool me-2"></i>View Integration</a>
												</div>
												<div class="status-toggle modal-status">
													<input type="checkbox" id="user1" class="check">
													<label for="user1" class="checktoggle"> </label>
												</div>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6">
										<div class="card">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<div class="d-flex align-items-center">
													<span class="avatar avatar-lg p-2 rounded bg-gray flex-shrink-0 me-2"><img src="{{URL::asset('build/img/icons/twitter-icon.svg')}}" alt="Img"></span>
													<h6>Twitter</h6>
												</div>
												<span class="badge bg-transparent-dark text-dark">Not Connected</span>
											</div>
											<div class="card-body pt-0">
												<p>Communicate and stay connected through the exchange of quick, frequent messages</p>
											</div>
											<div class="card-footer d-flex justify-content-between align-items-center">
												<div>
													<a href="#" class="btn btn-outline-light"><i class="ti ti-tool me-2"></i>View Integration</a>
												</div>
												<div class="status-toggle modal-status">
													<input type="checkbox" id="user2" class="check">
													<label for="user2" class="checktoggle"> </label>
												</div>
											</div>
										</div>
									</div>
									<div class="col-xxl-4 col-xl-6">
										<div class="card">
											<div class="card-header d-flex align-items-center justify-content-between border-0 mb-3 pb-0">
												<div class="d-flex align-items-center">
													<span class="avatar avatar-lg p-2 rounded bg-gray flex-shrink-0 me-2"><img src="{{URL::asset('build/img/icons/google-icon.svg')}}" alt="Img"></span>
													<h6>Google</h6>
												</div>
												<span class="badge bg-transparent-dark text-dark">Not Connected</span>
											</div>
											<div class="card-body pt-0">
												<p>Google has many special features to help you find exactly what you're looking for.</p>
											</div>
											<div class="card-footer d-flex justify-content-between align-items-center">
												<div>
													<a href="#" class="btn btn-outline-light"><i class="ti ti-tool me-2"></i>View Integration</a>
												</div>
												<div class="status-toggle modal-status">
													<input type="checkbox" id="user3" class="check">
													<label for="user3" class="checktoggle"> </label>
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


@endsection
