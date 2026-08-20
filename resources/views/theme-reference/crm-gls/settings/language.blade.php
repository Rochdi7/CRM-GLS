<?php $page = 'language'; ?>
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
							<a href="{{url('social-authentication')}}" class="d-block rounded p-2">Social Authentication</a>
							<a href="{{url('language')}}" class="d-block rounded p-2 active">Language</a>
						</div>
					</div>
					<div class="col-xxl-10 col-xl-9">
						<div class="border-start ps-3">
							<div class="d-flex align-items-center justify-content-between flex-wrap border-bottom pt-3 mb-3">
								<div class="mb-3">
									<h5 class="mb-1">Language</h5>
									<p>Personalize your Language settings of your website</p>
								</div>
								<div class="mb-3">
									<a href="#" class="btn btn-light me-2" data-bs-toggle="modal"
										data-bs-target="#import_file"><i class="ti ti-download me-2"></i>Import</a>
									<a href="#" class="btn btn-primary" data-bs-toggle="modal"
										data-bs-target="#add_language"><i
											class="ti ti-square-rounded-plus-filled me-2"></i>Add Language</a>
								</div>
							</div>
							<!-- Students List -->
							<div class="card">
								<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
									<h4 class="mb-3">Language</h4>
									<div class="d-flex align-items-center flex-wrap">
										<div class="input-icon-start mb-3 me-2 position-relative">
											<span class="icon-addon">
												<i class="ti ti-calendar"></i>
											</span>
											<input type="text" class="form-control date-range bookingrange" placeholder="Select"
											value="Academic Year : 2024 / 2025">
										</div>
										<div class="dropdown mb-3 me-2">
											<a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
												data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
													class="ti ti-filter me-2"></i>Filter</a>
											<div class="dropdown-menu drop-width">
												<form action="{{url('language')}}">
													<div class="d-flex align-items-center border-bottom p-3">
														<h4>Filter</h4>
													</div>
													<div class="p-3 pb-0 border-bottom">
														<div class="row">
															<div class="col-md-12">
																<div class="mb-3">
																	<label class="form-label">Language</label>
																	<select class="select">
																		<option>Select</option>
																		<option>English</option>
																		<option>Arabic</option>
																		<option>Chinese</option>
																	</select>
																</div>
															</div>
															<div class="col-md-12">
																<div class="mb-3">
																	<label class="form-label">Code</label>
																	<select class="select">
																		<option>Select</option>
																		<option>en</option>
																		<option>ar</option>
																		<option>zh</option>
																	</select>
																</div>
															</div>
														</div>
													</div>
													<div class="p-3 d-flex align-items-center justify-content-end">
														<a href="#" class="btn btn-light me-2">Reset</a>
														<button type="submit" class="btn btn-primary">Apply</button>
													</div>
												</form>
											</div>
										</div>
										<div class="dropdown mb-3">
											<a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
												data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z
											</a>
											<ul class="dropdown-menu p-3">
												<li>
													<a href="javascript:void(0);" class="dropdown-item rounded-1">
														Ascending
													</a>
												</li>
												<li>
													<a href="javascript:void(0);" class="dropdown-item rounded-1">
														Descending
													</a>
												</li>
												<li>
													<a href="javascript:void(0);" class="dropdown-item rounded-1">
														Recently Viewed
													</a>
												</li>
												<li>
													<a href="javascript:void(0);" class="dropdown-item rounded-1">
														Recently Added
													</a>
												</li>
											</ul>
										</div>
									</div>
								</div>
								<div class="card-body p-0 py-3">

									<!-- Student List -->
									<div class="custom-datatable-filter table-responsive">
										<table class="table datatable">
											<thead class="thead-light">
												<tr>
													<th class="no-sort">
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox" id="select-all">
														</div>
													</th>
													<th>Language</th>
													<th>Code</th>
													<th>RTL</th>
													<th>Status </th>
													<th class="no-sort">Action</th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td>English</td>
													<td>en</td>
													<td>
														<div class="form-check form-switch">
															<input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
														</div>
													</td>
													<td>
														<span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
													</td>
													<td>
														<div class="dropdown">
															<a href="#"
																class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
																data-bs-toggle="dropdown" aria-expanded="false">
																<i class="ti ti-dots-vertical fs-14"></i>
															</a>
															<ul class="dropdown-menu dropdown-menu-right p-3">
																<li>
																	<a class="dropdown-item rounded-1" href="#"><i class="ti ti-language me-2"></i>Make as Default</a>
																</li>
																<li>
																	<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_language"><i class="ti ti-edit-circle me-2"></i>Edit</a>
																</li>
																<li>
																	<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#language_setup"><i class="ti ti-settings me-2"></i>Set up</a>
																</li>
																<li>
																	<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
																</li>
															</ul>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td>Arabic</td>
													<td>ar</td>
													<td>
														<div class="form-check form-switch">
															<input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
														</div>
													</td>
													<td>
														<span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
													</td>
													<td>
														<div class="dropdown">
															<a href="#"
																class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
																data-bs-toggle="dropdown" aria-expanded="false">
																<i class="ti ti-dots-vertical fs-14"></i>
															</a>
															<ul class="dropdown-menu dropdown-menu-right p-3">
																<li>
																	<a class="dropdown-item rounded-1" href="#"><i class="ti ti-language me-2"></i>Make as Default</a>
																</li>
																<li>
																	<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_language"><i class="ti ti-edit-circle me-2"></i>Edit</a>
																</li>
																<li>
																	<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#language_setup"><i class="ti ti-settings me-2"></i>Set up</a>
																</li>
																<li>
																	<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
																</li>
															</ul>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td>Chinese</td>
													<td>zh</td>
													<td>
														<div class="form-check form-switch">
															<input class="form-check-input" type="checkbox" role="switch" id="switch-sm3">
														</div>
													</td>
													<td>
														<span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
													</td>
													<td>
														<div class="dropdown">
															<a href="#"
																class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
																data-bs-toggle="dropdown" aria-expanded="false">
																<i class="ti ti-dots-vertical fs-14"></i>
															</a>
															<ul class="dropdown-menu dropdown-menu-right p-3">
																<li>
																	<a class="dropdown-item rounded-1" href="#"><i class="ti ti-language me-2"></i>Make as Default</a>
																</li>
																<li>
																	<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_language"><i class="ti ti-edit-circle me-2"></i>Edit</a>
																</li>
																<li>
																	<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#language_setup"><i class="ti ti-settings me-2"></i>Set up</a>
																</li>
																<li>
																	<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
																</li>
															</ul>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check form-check-md">
															<input class="form-check-input" type="checkbox">
														</div>
													</td>
													<td>Hindi</td>
													<td>hi</td>
													<td>
														<div class="form-check form-switch">
															<input class="form-check-input" type="checkbox" role="switch" id="switch-sm4">
														</div>
													</td>
													<td>
														<span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
													</td>
													<td>
														<div class="dropdown">
															<a href="#"
																class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
																data-bs-toggle="dropdown" aria-expanded="false">
																<i class="ti ti-dots-vertical fs-14"></i>
															</a>
															<ul class="dropdown-menu dropdown-menu-right p-3">
																<li>
																	<a class="dropdown-item rounded-1" href="#"><i class="ti ti-language me-2"></i>Make as Default</a>
																</li>
																<li>
																	<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#edit_language"><i class="ti ti-edit-circle me-2"></i>Edit</a>
																</li>
																<li>
																	<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#language_setup"><i class="ti ti-settings me-2"></i>Set up</a>
																</li>
																<li>
																	<a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
																</li>
															</ul>
														</div>
													</td>
												</tr>
											</tbody>
										</table>
									</div>
									<!-- /Student List -->
								</div>
							</div>
							<!-- /Students List -->
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- /Page Wrapper -->


    @component('components.modal-popup')
    @endcomponent
@endsection
