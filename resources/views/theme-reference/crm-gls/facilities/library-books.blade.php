<?php $page = 'library-books'; ?>
@extends('layout.mainlayout')
@section('content')
	<!-- Page Wrapper -->
	<div class="page-wrapper">
		<div class="content">
			@component('components.breadcrumb')
			@slot('title')
			Books
			@endslot
			@slot('item1')
			Dashboard
			@endslot
			@slot('item2')
			Management
			@endslot
			@slot('item3')
			Books
			@endslot
		   @endcomponent
			

			<!-- Students List -->
			<div class="card">
				<div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
					<h4 class="mb-3">Books</h4>
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
								<form action="{{url('library-books')}}">
									<div class="d-flex align-items-center border-bottom p-3">
										<h4>Filter</h4>
									</div>
									<div class="p-3 border-bottom">
										<div class="row">
											<div class="col-md-6">
												<div class="mb-3">
													<label class="form-label">Book No</label>
													<select class="select">
														<option>Select</option>
														<option>501</option>
														<option>502</option>
														<option>503</option>
														<option>504</option>
													</select>
												</div>
											</div>
											<div class="col-md-6">
												<div class="mb-3">
													<label class="form-label">Subject</label>
													<select class="select">
														<option>Select</option>
														<option>History</option>
														<option>Science</option>
														<option>Literary</option>
														<option>Adventure</option>
													</select>
												</div>
											</div>
											<div class="col-md-12">
												<div class="mb-3">
													<label class="form-label">More Filter</label>
													<select class="select">
														<option>Select</option>
														<option>ID</option>
														<option>Book Name</option>
														<option>Book No</option>
														<option>Publisher</option>
														<option>Author</option>
														<option>Subject</option>
														<option>Rack No</option>
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
									<th>ID</th>
									<th>Book Name</th>
									<th>Book No</th>
									<th>Publisher</th>
									<th>Author</th>
									<th>Subject</th>
									<th>Rack No</th>
									<th>Qty</th>
									<th>Available</th>
									<th>Price</th>
									<th>Post Date</th>
									<th>Action</th>

								</tr>
							</thead>
							<tbody>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">LB864723</a></td>
									<td>Echoes of Eternity</td>
									<td>501
									</td>
									<td>Aurora Press</td>
									<td> Isabella Rivers</td>
									<td> History</td>
									<td> 6550</td>
									<td> 150</td>
									<td> 120</td>
									<td> $300</td>
									<td>25 Apr 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<div class="dropdown">
												<a href="#"
													class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
													data-bs-toggle="dropdown" aria-expanded="false">
													<i class="ti ti-dots-vertical fs-14"></i>
												</a>
												<ul class="dropdown-menu dropdown-menu-right p-3">

													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal"
															data-bs-target="#edit_library_book"><i
																class="ti ti-edit-circle me-2"></i>Edit</a>
													</li>
													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal" data-bs-target="#delete-modal"><i
																class="ti ti-trash-x me-2"></i>Delete</a>
													</li>
												</ul>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">LB864722</a></td>
									<td>The Stars of Eldorado</td>
									<td>502
									</td>
									<td>Nebula Press</td>
									<td>Amanda Grayson</td>
									<td>Science </td>
									<td>6551</td>
									<td>200</td>
									<td>180</td>
									<td>$280</td>
									<td>28 Apr 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<div class="dropdown">
												<a href="#"
													class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
													data-bs-toggle="dropdown" aria-expanded="false">
													<i class="ti ti-dots-vertical fs-14"></i>
												</a>
												<ul class="dropdown-menu dropdown-menu-right p-3">

													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal"
															data-bs-target="#edit_library_book"><i
																class="ti ti-edit-circle me-2"></i>Edit</a>
													</li>
													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal" data-bs-target="#delete-modal"><i
																class="ti ti-trash-x me-2"></i>Delete</a>
													</li>
												</ul>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">LB864722</a></td>
									<td>The Glass Painter</td>
									<td>503</td>
									<td>Artisan Reads</td>
									<td>Isabel Marquez</td>
									<td>Literary</td>
									<td>6552</td>
									<td>180</td>
									<td>160</td>
									<td>$320</td>
									<td>04 May 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<div class="dropdown">
												<a href="#"
													class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
													data-bs-toggle="dropdown" aria-expanded="false">
													<i class="ti ti-dots-vertical fs-14"></i>
												</a>
												<ul class="dropdown-menu dropdown-menu-right p-3">

													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal"
															data-bs-target="#edit_library_book"><i
																class="ti ti-edit-circle me-2"></i>Edit</a>
													</li>
													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal" data-bs-target="#delete-modal"><i
																class="ti ti-trash-x me-2"></i>Delete</a>
													</li>
												</ul>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">LB864720</a></td>
									<td>Beyond the Edge </td>
									<td>504</td>
									<td>Explorer's Press</td>
									<td>Leo Finnegan</td>
									<td>Adventure</td>
									<td>6553</td>
									<td>120</td>
									<td>100</td>
									<td>$350</td>
									<td>18 May 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<div class="dropdown">
												<a href="#"
													class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
													data-bs-toggle="dropdown" aria-expanded="false">
													<i class="ti ti-dots-vertical fs-14"></i>
												</a>
												<ul class="dropdown-menu dropdown-menu-right p-3">

													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal"
															data-bs-target="#edit_library_book"><i
																class="ti ti-edit-circle me-2"></i>Edit</a>
													</li>
													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal" data-bs-target="#delete-modal"><i
																class="ti ti-trash-x me-2"></i>Delete</a>
													</li>
												</ul>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">LB864719</a></td>
									<td>Shadow Symphony</td>
									<td>505</td>
									<td>Harmony House</td>
									<td>Claire Vincent</td>
									<td>Gothic</td>
									<td>6554</td>
									<td>220</td>
									<td>160</td>
									<td>$280</td>
									<td>20 May 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<div class="dropdown">
												<a href="#"
													class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
													data-bs-toggle="dropdown" aria-expanded="false">
													<i class="ti ti-dots-vertical fs-14"></i>
												</a>
												<ul class="dropdown-menu dropdown-menu-right p-3">

													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal"
															data-bs-target="#edit_library_book"><i
																class="ti ti-edit-circle me-2"></i>Edit</a>
													</li>
													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal" data-bs-target="#delete-modal"><i
																class="ti ti-trash-x me-2"></i>Delete</a>
													</li>
												</ul>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">LB864718</a></td>
									<td>The Last Library</td>
									<td>506
									</td>
									<td>Archive Publishing</td>
									<td>Marcus Brewster</td>
									<td>Dystopian</td>
									<td>6555</td>
									<td>170</td>
									<td>150</td>
									<td>$250</td>
									<td>16 Jun 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<div class="dropdown">
												<a href="#"
													class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
													data-bs-toggle="dropdown" aria-expanded="false">
													<i class="ti ti-dots-vertical fs-14"></i>
												</a>
												<ul class="dropdown-menu dropdown-menu-right p-3">

													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal"
															data-bs-target="#edit_library_book"><i
																class="ti ti-edit-circle me-2"></i>Edit</a>
													</li>
													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal" data-bs-target="#delete-modal"><i
																class="ti ti-trash-x me-2"></i>Delete</a>
													</li>
												</ul>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">LB864717</a></td>
									<td>The Saffron Tide</td>
									<td>507</td>
									<td>Indus Books</td>
									<td>Rajiv Anand</td>
									<td>History</td>
									<td>6556</td>
									<td>140</td>
									<td>100</td>
									<td>$200</td>
									<td>20 Jun 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<div class="dropdown">
												<a href="#"
													class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
													data-bs-toggle="dropdown" aria-expanded="false">
													<i class="ti ti-dots-vertical fs-14"></i>
												</a>
												<ul class="dropdown-menu dropdown-menu-right p-3">

													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal"
															data-bs-target="#edit_library_book"><i
																class="ti ti-edit-circle me-2"></i>Edit</a>
													</li>
													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal" data-bs-target="#delete-modal"><i
																class="ti ti-trash-x me-2"></i>Delete</a>
													</li>
												</ul>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">LB864716</a></td>
									<td>Windswept</td>
									<td>508</td>
									<td>Coastal Press</td>
									<td>Lydia Ramsey</td>
									<td>Romance</td>
									<td>6557</td>
									<td>300</td>
									<td>270</td>
									<td>$300</td>
									<td>24 Jun 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<div class="dropdown">
												<a href="#"
													class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
													data-bs-toggle="dropdown" aria-expanded="false">
													<i class="ti ti-dots-vertical fs-14"></i>
												</a>
												<ul class="dropdown-menu dropdown-menu-right p-3">

													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal"
															data-bs-target="#edit_library_book"><i
																class="ti ti-edit-circle me-2"></i>Edit</a>
													</li>
													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal" data-bs-target="#delete-modal"><i
																class="ti ti-trash-x me-2"></i>Delete</a>
													</li>
												</ul>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">LB864715</a></td>
									<td>Frostbound Throne</td>
									<td>509</td>
									<td>Frozen Realms</td>
									<td>Viktor Korneev</td>
									<td>Fantasy</td>
									<td>6558</td>
									<td>320</td>
									<td>200</td>
									<td>$350</td>
									<td>$10 Jul 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<div class="dropdown">
												<a href="#"
													class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
													data-bs-toggle="dropdown" aria-expanded="false">
													<i class="ti ti-dots-vertical fs-14"></i>
												</a>
												<ul class="dropdown-menu dropdown-menu-right p-3">

													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal"
															data-bs-target="#edit_library_book"><i
																class="ti ti-edit-circle me-2"></i>Edit</a>
													</li>
													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal" data-bs-target="#delete-modal"><i
																class="ti ti-trash-x me-2"></i>Delete</a>
													</li>
												</ul>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<div class="form-check form-check-md">
											<input class="form-check-input" type="checkbox">
										</div>
									</td>
									<td><a href="#" class="link-primary">LB864714</a></td>
									<td>The Last Alchemist</td>
									<td>510</td>
									<td>Alchemy Arts</td>
									<td>Philip Dubois</td>
									<td>Mystery</td>
									<td>6559</td>
									<td>190</td>
									<td>170</td>
									<td>$400</td>
									<td>12 Jul 2024</td>
									<td>
										<div class="d-flex align-items-center">
											<div class="dropdown">
												<a href="#"
													class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
													data-bs-toggle="dropdown" aria-expanded="false">
													<i class="ti ti-dots-vertical fs-14"></i>
												</a>
												<ul class="dropdown-menu dropdown-menu-right p-3">

													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal"
															data-bs-target="#edit_library_book"><i
																class="ti ti-edit-circle me-2"></i>Edit</a>
													</li>
													<li>
														<a class="dropdown-item rounded-1" href="#"
															data-bs-toggle="modal" data-bs-target="#delete-modal"><i
																class="ti ti-trash-x me-2"></i>Delete</a>
													</li>
												</ul>
											</div>
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
	<!-- /Page Wrapper -->

      @component('components.modal-popup')        
    @endcomponent
@endsection
