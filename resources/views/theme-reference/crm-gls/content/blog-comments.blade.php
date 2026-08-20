<?php $page = 'blog-comments'; ?>
@extends('layout.mainlayout')
@section('content')
   <!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">
				@component('components.breadcrumb')
				@slot('title')
				Comments
				@endslot
				@slot('item1')
				Dashboard
				@endslot
				@slot('item2')
				Pages
				@endslot
				@slot('item3')
				Comments
				@endslot
			   @endcomponent
				

				<div class="card">
					<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
						<h4 class="mb-3">Comments List</h4>
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
									<form action="{{url('blog-comments')}}">
										<div class="d-flex align-items-center border-bottom p-3">
											<h4>Filter</h4>
										</div>
										<div class="p-3 border-bottom">
											<div class="row">
												<div class="col-md-12">
													<div class="mb-0">
														<label class="form-label">Date</label>
														<select class="select">
															<option>Select</option>
															<option>25 May 2024</option>
															<option>15 May 2024</option>
															<option>27 Apr 2024</option>
														</select>
													</div>
												</div>
											</div>
										</div>
										<div class="p-3 d-flex align-items-center justify-content-end">
											<a href="#" class="btn btn-light me-3">Reset</a>
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
										<a href="javascript:void(0);" class="dropdown-item rounded-1 active">
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
						<!-- /Comments List -->
						<div class="custom-datatable-filter
						table-responsive">
							<table class="table datatable">
								<thead class="thead-light">
									<tr>
										<th class="no-sort">
											<div class="form-check form-check-md">
												<input class="form-check-input" type="checkbox" id="select-all">
											</div>
										</th>
										<th>Comment</th>
										<th>Date</th>
										<th>Review</th>
										<th>Blog </th>
										<th>By</th>
										<th>Action</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td class="text-content">I absolutely loved this article!... The insights</td>
										<td class="text-gray-9">25 May 2024</td>
										<td>
											<div class="rating">
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<span class="rating-rate">5.0</span>
											</div>
										</td>
										<td class="text-gray-9 text-content">5 Effective Strategies to Boost Your
											Productivity
										</td>
										<td>Rubiaana</td>
										<td>
											<select class="select">
												<option>Unpublish</option>
												<option>Publish</option>
											</select>
										</td>
										<td><a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
												class="delete-icon"><i class="ti ti-trash-x"></i></a>
											</td>
									</tr>
									<tr>
										<td>
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td class="text-content">Self-improvement is a continuous journey</td>
										<td class="text-gray-9">15 May 2024</td>
										<td>
											<div class="rating">
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<span class="rating-rate">5.0</span>
											</div>
										</td>
										<td class="text-gray-9 text-content">Personal development and self-improvement
										</td>
										<td>Jordan</td>
										<td>
											<select class="select">
												<option>Unpublish</option>
												<option>Publish</option>
											</select>
										</td>
										<td><a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
												class="delete-icon"><i class="ti ti-trash-x"></i></a></td>
									</tr>
									<tr>
										<td>
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td class="text-content">Prioritizing tasks makes a huge difference.</td>
										<td class="text-gray-9">12 May 2024</td>
										<td>
											<div class="rating">
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<span class="rating-rate">5.0</span>
											</div>
										</td>
										<td class="text-gray-9 text-content">Prioritize Your Tasks
										</td>
										<td>Daniel</td>
										<td>
											<select class="select">
												<option>Unpublish</option>
												<option>Publish</option>
											</select>
										</td>
										<td><a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
												class="delete-icon"><i class="ti ti-trash-x"></i></a></td>
									</tr>
									<tr>
										<td>
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td class="text-content">Turning off notifications helped me focus</td>
										<td class="text-gray-9">27 Apr 2024</td>
										<td>
											<div class="rating">
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<span class="rating-rate">5.0</span>
											</div>
										</td>
										<td class="text-gray-9 text-content">How to eliminate Eliminate Distractions
										</td>
										<td>Letitia</td>
										<td>
											<select class="select">
												<option>Unpublish</option>
												<option>Publish</option>
											</select>
										</td>
										<td><a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
												class="delete-icon"><i class="ti ti-trash-x"></i></a></td>
									</tr>
									<tr>
										<td>
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td class="text-content">Regular breaks improve my retention.</td>
										<td class="text-gray-9">16 Apr 2024</td>
										<td>
											<div class="rating">
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<span class="rating-rate">5.0</span>
											</div>
										</td>
										<td class="text-gray-9 text-content">Steps to Take Regular Breaks before learning
										</td>
										<td>Henrita</td>
										<td>
											<select class="select">
												<option>Unpublish</option>
												<option>Publish</option>
											</select>
										</td>
										<td><a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
												class="delete-icon"><i class="ti ti-trash-x"></i></a></td>
									</tr>
									<tr>
										<td>
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td class="text-content">Using a planner keeps me on track</td>
										<td class="text-gray-9">10 Apr 2024</td>
										<td>
											<div class="rating">
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<span class="rating-rate">5.0</span>
											</div>
										</td>
										<td class="text-gray-9 text-content">Using Time Management Techniques
										</td>
										<td>Ziatan</td>
										<td>
											<select class="select">
												<option>Unpublish</option>
												<option>Publish</option>
											</select>
										</td>
										<td><a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
												class="delete-icon"><i class="ti ti-trash-x"></i></a></td>
									</tr>
									<tr>
										<td>
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td class="text-content">Great strategies for schools!</td>
										<td class="text-gray-9">24 Mar 2024</td>
										<td>
											<div class="rating">
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<span class="rating-rate">5.0</span>
											</div>
										</td>
										<td class="text-gray-9 text-content">Effective Strategies for School Administration
										</td>
										<td>Jason</td>
										<td>
											<select class="select">
												<option>Unpublish</option>
												<option>Publish</option>
											</select>
										</td>
										<td><a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
												class="delete-icon"><i class="ti ti-trash-x"></i></a></td>
									</tr>
									<tr>
										<td>
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td class="text-content">Excited to learn about tech in schools!</td>
										<td class="text-gray-9">18 Mar 2024</td>
										<td>
											<div class="rating">
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<span class="rating-rate">5.0</span>
											</div>
										</td>
										<td class="text-gray-9 text-content">Implementing Technology in School Management
										</td>
										<td>Gloria </td>
										<td>
											<select class="select">
												<option>Unpublish</option>
												<option>Publish</option>
											</select>
										</td>
										<td><a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
												class="delete-icon"><i class="ti ti-trash-x"></i></a></td>
									</tr>
									<tr>
										<td>
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td class="text-content">Communication is key in any organization</td>
										<td class="text-gray-9">11 Mar 2024</td>
										<td>
											<div class="rating">
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<span class="rating-rate">5.0</span>
											</div>
										</td>
										<td class="text-gray-9 text-content">Building Strong Communication Channels in Schools
										</td>
										<td>Elizabeth</td>
										<td>
											<select class="select">
												<option>Unpublish</option>
												<option>Publish</option>
											</select>
										</td>
										<td><a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
												class="delete-icon"><i class="ti ti-trash-x"></i></a></td>
									</tr>
									<tr>
										<td>
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td class="text-content">Improving staff training sounds promising!</td>
										<td class="text-gray-9">20 Feb 2024</td>
										<td>
											<div class="rating">
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<span class="rating-rate">5.0</span>
											</div>
										</td>
										<td class="text-gray-9 text-content">Effective Staff Training and Development in Schools
										</td>
										<td>Michael</td>
										<td>
											<select class="select">
												<option>Unpublish</option>
												<option>Publish</option>
											</select>
										</td>
										<td><a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
												class="delete-icon"><i class="ti ti-trash-x"></i></a></td>
									</tr>
									<tr>
										<td>
											<label class="checkboxs">
												<input type="checkbox">
												<span class="checkmarks"></span>
											</label>
										</td>
										<td class="text-content">Safety is paramount in schools</td>
										<td class="text-gray-9">19 Feb 2024</td>
										<td>
											<div class="rating">
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<i class="ti ti-star-filled filled"></i>
												<span class="rating-rate">5.0</span>
											</div>
										</td>
										<td class="text-gray-9 text-content">Ensuring Safety and Security Measures in Schools
										</td>
										<td>Richard </td>
										<td>
											<select class="select">
												<option>Unpublish</option>
												<option>Publish</option>
											</select>
										</td>
										<td><a href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"
												class="delete-icon"><i class="ti ti-trash-x"></i></a></td>
									</tr>
								</tbody>
							</table>
						</div>
						<!-- /Comments List -->
					</div>
				</div>
			</div>
		</div>
		<!-- /Page Wrapper -->
    @component('components.modal-popup')
    @endcomponent
@endsection
