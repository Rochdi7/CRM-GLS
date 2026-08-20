@if (Route::is(['chat']))
   <!-- Add Transfer -->
		<div class="modal fade" id="add-units">
			<div class="modal-dialog purchase modal-dialog-centered stock-adjust-modal">
				<div class="modal-content">
					<div class="page-wrapper-new p-0">
						<div class="content">
							<div class="modal-header border-0 custom-modal-header">
								<div class="page-title">
									<h4>Add Transfer</h4>
								</div>
								<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
							<div class="modal-body custom-modal-body">
								<div class="row">
									<div class="col-lg-4 col-md-6 col-sm-12">
										<div class="input-blocks">
											<label>Date</label>

											<div class="input-groupicon calender-input">
												<i data-feather="calendar" class="info-img"></i>
												<input type="text" class="datetimepicker form-control" placeholder="Select Date">
											</div>
										</div>
									</div>
									<div class="col-lg-4 col-md-6 col-sm-12">
										<div class="input-blocks">
											<label>From</label>
											<select class="select">
												<option>Choose</option>
												<option>Store 1</option>
											</select>
										</div>
									</div>
									<div class="col-lg-4 col-md-6 col-sm-12">
										<div class="input-blocks">
											<label>To</label>
											<select class="select">
												<option>Choose</option>
												<option>Store 2</option>
											</select>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-lg-12">
										<div class="input-blocks">
											<label>Product Name</label>
											<input type="text" placeholder="Please type product code and select">
										</div>
									</div>
									<div class="col-lg-12">
										<div class="modal-body-table">
											<div class="table-responsive">
												<table class="table  datanew">
													<thead>
														<tr>
															<th>Product</th>
															<th>Qty</th>
															<th>Purchase Price($)</th>
															<th>Discount($)</th>
															<th>Tax(%)</th>
															<th>Tax Amount($)</th>
															<th>Unit Cost($)</th>
															<th>Total Cost(%)</th>
														</tr>
													</thead>
													<tbody>
														<tr>
															<td class="p-5"></td>
															<td class="p-5"></td>
															<td class="p-5"></td>
															<td class="p-5"></td>
															<td class="p-5"></td>
															<td class="p-5"></td>
															<td class="p-5"></td>
															<td class="p-5"></td>
														</tr>													
													</tbody>
												</table>
											</div>
										</div>

									</div>
									<div class="row">
										<div class="col-lg-3 col-md-6 col-sm-12">
											<div class="input-blocks">
												<label>Order Tax</label>
												<input type="text" value="0">
											</div>
										</div>
										<div class="col-lg-3 col-md-6 col-sm-12">
											<div class="input-blocks">
												<label>Discount</label>
												<input type="text" value="0">
											</div>
										</div>
										<div class="col-lg-3 col-md-6 col-sm-12">
											<div class="input-blocks">
												<label>Shipping</label>
												<input type="text" value="0">
											</div>
										</div>
										<div class="col-lg-3 col-md-6 col-sm-12">
											<div class="input-blocks">
												<label>Status</label>
												<select class="select">
													<option>Choose</option>
													<option>Sent</option>
													<option>Pending</option>
												</select>
											</div>
										</div>
									</div>
								</div>

								<div class="col-lg-12">
									<div class="input-blocks summer-description-box">
										<label>Notes</label>
										<div id="summernote"></div>
									</div>
								</div>
								<div class="col-lg-12">
									<div class="modal-footer-btn">
										<a href="javascript:void(0);" class="btn btn-cancel me-2" data-bs-dismiss="modal">Cancel</a>
										<a href="javascript:void(0);" class="btn btn-submit">Submit</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- /Add Transfer -->

		<!-- Edit Transfer -->
		<div class="modal fade" id="edit-units">
			<div class="modal-dialog purchase modal-dialog-centered stock-adjust-modal">
				<div class="modal-content">
					<div class="page-wrapper-new p-0">
						<div class="content">
							<div class="modal-header border-0 custom-modal-header">
								<div class="page-title">
									<h4>Edit Transfer</h4>
								</div>
								<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
							<div class="modal-body custom-modal-body">
								<div >
									<div>
										<div class="row">
											<div class="col-lg-4 col-md-6 col-sm-12">
												<div class="input-blocks">
													<label>Date</label>
			
													<div class="input-groupicon calender-input">
														<i data-feather="calendar" class="info-img"></i>
														<input type="text" class="datetimepicker" placeholder="19 Jan 2023">
													</div>
												</div>
											</div>
											<div class="col-lg-4 col-md-6 col-sm-12">
												<div class="input-blocks">
													<label>From</label>
													<select class="select">
														<option>Store 1</option>
														<option>Choose</option>
													</select>
												</div>
											</div>
											<div class="col-lg-4 col-md-6 col-sm-12">
												<div class="input-blocks">
													<label>To</label>
													<select class="select">
														<option>Store 2</option>
														<option>Choose</option>
													</select>
												</div>
											</div>
											<div class="col-lg-12 col-sm-6 col-12">
												<div class="input-blocks">
													<label>Product</label>
													<div class="input-groupicon">
														<input type="text"
															placeholder="Scan/Search Product by code and select...">
														<div class="addonset">
															<img src="{{URL::asset('build/img/icons/scanners.svg')}}" alt="img">
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-lg-12">
											<div class="modal-body-table total-orders">
												<div class="table-responsive">
													<table class="table">
														<thead>
															<tr>
																<th>Product Name</th>
																<th>QTY</th>
																<th>Purchase Price($) </th>
																<th>Discount($) </th>
																<th>Tax %</th>
																<th>Tax Amount($)</th>
																<th class="text-end">Unit Cost($)</th>
																<th class="text-end">Total Cost ($) </th>
																<th></th>
															</tr>
														</thead>
														<tbody>
															<tr>
																<td>
																	<div class="productimgname">
																		<a href="javascript:void(0);" class="product-img stock-img">
																			<img src="{{URL::asset('build/img/supplier/stock-img-01.png')}}" alt="product">
																		</a>
																		<a href="javascript:void(0);">Nike Jordan</a>
																	</div>
																</td>
																<td><div class="product-quantity">
																	<span class="quantity-btn">+<i data-feather="plus-circle" class="plus-circle"></i></span>
																	<input type="text" class="quntity-input" value="10">
																	<span class="quantity-btn"><i data-feather="minus-circle" class="feather-search"></i></span>
																</div></td>
																<td>2000</td>
																<td>500.00</td>
																<td>0.00</td>
																<td>0.00</td>
																<td class="text-end">0.00</td>
																<td class="text-end">1500</td>
																<td>
																	<a class="delete-set"><img
																			src="{{URL::asset('build/img/icons/delete.svg')}}" alt="svg"></a>
																</td>
															</tr>
														</tbody>
													</table>
												</div>
											</div>
											</div>
										</div>
										<div class="row">
											<div class="col-lg-12 float-md-right">
												<div class="total-order">
													<ul>
														<li>
															<h4>Order Tax</h4>
															<h5>$ 0.00</h5>
														</li>
														<li>
															<h4>Discount</h4>
															<h5>$ 0.00</h5>
														</li>
														<li>
															<h4>Shipping</h4>
															<h5>$ 0.00</h5>
														</li>
														<li class="total">
															<h4>Grand Total</h4>
															<h5>$1500.00</h5>
														</li>
													</ul>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-lg-3 col-sm-6 col-12">
												<div class="input-blocks">
													<label>Order Tax</label>
													<input type="text" value="0">
												</div>
											</div>
											<div class="col-lg-3 col-sm-6 col-12">
												<div class="input-blocks">
													<label>Discount</label>
													<input type="text" value="0">
												</div>
											</div>
											<div class="col-lg-3 col-sm-6 col-12">
												<div class="input-blocks">
													<label>Shipping</label>
													<input type="text" value="0">
												</div>
											</div>
											<div class="col-lg-3 col-sm-6 col-12">
												<div class="input-blocks">
													<label>Status</label>
													<select class="select">
														<option>Sent</option>
														<option>Pending</option>
													</select>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="col-lg-12">
									<div class="input-blocks summer-description-box">
										<label>Description</label>
										<div id="summernote2">
											<p>These shoes are made with the highest quality materials. </p>
										</div>
									</div>
								</div>
								<div class="col-lg-12">
									<div class="modal-footer-btn">
										<a href="javascript:void(0);" class="btn btn-cancel me-2" data-bs-dismiss="modal">Cancel</a>
										<a href="javascript:void(0);" class="btn btn-submit">Save Changes</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- /Edit Transfer -->

		<!-- Import Purchase -->
		<div class="modal fade" id="view-notes">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="page-wrapper-new p-0">
						<div class="content">
							<div class="modal-header border-0 custom-modal-header">
								<div class="page-title">
									<h4>Import Transfer</h4>
								</div>
								<button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
							<div class="modal-body custom-modal-body">
								<div class="row">
									<div class="col-lg-4 col-sm-6 col-12">
										<div class="input-blocks">
											<label>From</label>
											<select class="select">
												<option>Choose</option>
												<option>Store 1</option>
											</select>
										</div>
									</div>
									<div class="col-lg-4 col-sm-6 col-12">
										<div class="input-blocks">
											<label>To</label>
											<select class="select">
												<option>Choose</option>
												<option>Store 2</option>
											</select>
										</div>
									</div>
									<div class="col-lg-4 col-sm-6 col-12">
										<div class="input-blocks">
											<label>Satus</label>
											<select class="select">
												<option>Choose</option>
												<option>Sent</option>
												<option>Pending</option>
											</select>
										</div>
									</div>
									<div class="col-lg-12 col-sm-6 col-12">
										<div class="row">
											<div>
												<!-- <div class="input-blocks download">
													<a class="btn btn-submit">Download Sample File</a>
												</div> -->
												<div class="modal-footer-btn download-file">
													<a href="javascript:void(0)" class="btn btn-submit">Download Sample File</a>
												</div>
											</div>
										</div>
									</div>
									<div class="col-lg-12">
										<div class="input-blocks image-upload-down">
											<label>	Upload CSV File</label>
											<div class="image-upload download">
												<input type="file">
												<div class="image-uploads">
													<img src="{{URL::asset('build/img/download-img.png')}}" alt="img">
													<h4>Drag and drop a <span>file to upload</span></h4>
												</div>
											</div>
										</div>
									</div>
									<div class="col-lg-12 col-sm-6 col-12">
										<div class="input-blocks">
											<label>Shipping</label>
											<input type="text" class="form-control">
										</div>
									</div>
								</div>
								<div class="col-lg-12">
									<div class="input-blocks summer-description-box transfer">
										<label>Description</label>
										<div id="summernote3">
										</div>
										<p>Maximum 60 Characters</p>
									</div>
								</div>
									<div class="col-lg-12">
										<div class="modal-footer-btn">
											<a href="javascript:void(0);" class="btn btn-cancel me-2" data-bs-dismiss="modal">Cancel</a>
											<a href="{{url('purchase-list')}}" class="btn btn-submit">Submit</a>
										</div>
									</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- /Import Purchase -->
@endif
@if (Route::is(['security-settings']))
<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('security-settings')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->
@endif
@if (Route::is(['calendar']))
<!-- Add Event Modal -->
<div class="modal fade" id="add_event">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Event</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('calendar')}}">
                <div class="modal-body pb-0">
                    <div class="mb-3">
                        <label class="form-label">Event Name <span class="text-danger">*</span></label>
                        <input class="form-control" type="text">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Event Date <span class="text-danger">*</span></label>
                        <div class="cal-icon">
                            <input class="form-control " type="date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Event</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Event Modal -->

<!-- Add Category Modal -->
<div class="modal fade" id="add_new_event">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Category</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('calendar')}}">
                <div class="modal-body pb-0">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input class="form-control form-white" placeholder="Enter name" type="text" name="category-name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Choose Category Color</label>
                        <select class="select">
                            <option>Choose a color...</option>
                            <option value="success">Success</option>
                            <option value="danger">Danger</option>
                            <option value="info">Info</option>
                            <option value="primary">Primary</option>
                            <option value="warning">Warning</option>
                            <option value="inverse">Inverse</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Category Modal -->

@endif

@if (Route::is(['todo']))
  	<!-- Add Todo -->
		<div class="modal fade" id="note-units">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Todo</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('todo')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-12">
									<div class="mb-3">
										<label class="form-label">Todo Title</label>
										<input type="text" class="form-control">
									</div>											
								</div>
								<div class="col-12">
									<div class="mb-3">
										<label class="form-label">Assignee</label>
										<select class="select">
											<option>Choose</option>
											<option>Kathleen</option>											
											<option>Gifford</option>
										</select>
									</div>
								</div>
								<div class="col-6">
									<div class="mb-3">
										<label class="form-label">Tag</label>
										<select class="select">
											<option>Select</option>
											<option>pending</option>
											<option>Onhold</option>
											<option>Inprogress</option>
											<option>Done</option>
										</select>
									</div>
								</div>
								<div class="col-6">
									<div class="mb-3">
										<label class="form-label">Priority</label>
										<select class="select">
											<option>Select</option>
											<option>Medium</option>
											<option>High</option>
											<option>Low</option>
										</select>
									</div>
								</div>
								<div class="col-6">
									<div class="input-blocks todo-calendar">
										<label class="form-label">Due Date</label>                                                
										<div class="input-groupicon calender-input">
											<input type="text" class="form-control  datetimepicker" placeholder="Select">
										</div>
									</div>
								</div>
								<div class="col-6">
									<div class="mb-3">
										<label class="form-label">Status</label>
										<select class="select">
											<option>Select</option>
											<option>Active</option>
											<option>Inactive</option>
										</select>
									</div>
								</div>
								<div class="col-lg-12">
									<div class="mb-0 summer-description-box notes-summernote">
										<label class="form-label">Descriptions</label>
										<div id="summernote"></div>
										<p>Maximum 60 Characters</p>
									</div>											
								</div>
							</div>	
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn btn-primary">Submit</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Todo -->

		<!-- Edit Todo -->
		<div class="modal fade" id="edit-note-units">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Todo</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('todo')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-12">
									<div class="mb-3">
										<label class="form-label">Todo Title</label>
										<input type="text" class="form-control" value="Team meet at Starbucks">
									</div>											
								</div>
								<div class="col-12">
									<div class="mb-3">
										<label class="form-label">Assignee</label>
										<select class="select">
											<option>Choose</option>
											<option selected>Kathleen</option>			
											<option>Gifford</option>
										</select>
									</div>
								</div>
								<div class="col-6">
									<div class="mb-3">
										<label class="form-label">Tag</label>
										<select class="select">
											<option>Select</option>
											<option selected>pending</option>
											<option>Onhold</option>
											<option>Inprogress</option>
											<option>Done</option>
										</select>
									</div>
								</div>
								<div class="col-6">
									<div class="mb-3">
										<label class="form-label">Priority</label>
										<select class="select">
											<option>Select</option>
											<option selected>Medium</option>
											<option>High</option>
											<option>Low</option>
										</select>
									</div>
								</div>
								<div class="col-6">
									<div class="input-blocks todo-calendar">
										<label class="form-label">Due Date</label>                                                
										<div class="input-groupicon calender-input">
											<input type="text" class="form-control datetimepicker" placeholder="Select" value="13 Aug 1992">
										</div>
									</div>
								</div>
								<div class="col-6">
									<div class="mb-3">
										<label class="form-label">Status</label>
										<select class="select">
											<option>Select</option>
											<option selected>Active</option>
											<option>Inactive</option>
										</select>
									</div>
								</div>
								<div class="col-lg-12">
									<div class="mb-0 summer-description-box notes-summernote">
										<label class="form-label">Descriptions</label>
										<div id="summernote"></div>
										<p>Maximum 60 Characters</p>
									</div>											
								</div>
							</div>	
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Todo -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('todo')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

		<!-- View Note -->
		<div class="modal fade" id="view-note-units">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="page-wrapper-new p-0">
						<div class="content">
							<div class="modal-header">
								<div class="d-flex align-items-center">
									<h4 class="modal-title me-3">Todo</h4>
									<p class="text-info">Personal</p>
								</div>
								<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
									<i class="ti ti-x"></i>
								</button>
							</div>
							<div class="modal-body">
								<div class="row">
									<div class="col-12">
										<div>
											<h4 class="mb-2">Meet Lisa to discuss project details</h4>
											<p>Hiking is a long, vigorous walk, usually on trails or footpaths in the countryside.
												Walking for pleasure developed in Europe during the eighteenth century.
												Religious pilgrimages have existed much longer but they involve walking long
												distances for a spiritual purpose associated with specific religions and also
												we achieve inner peace while we hike at a local park.</p>

												<p class="badge bg-outline-danger d-inline-flex align-items-center mb-0"><i class="fas fa-circle fs-6 me-1"></i> High</p>
										</div>	
									</div>
								</div>	
							</div>
							<div class="modal-footer">
								<a href="#" class="btn btn-danger" data-bs-dismiss="modal">Close</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- /View Note -->
@endif

@if (Route::is(['notes']))
   <!-- Add Note -->
		<div class="modal fade" id="note-units">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Notes</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('notes')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-12">
									<div class="mb-3">
										<label class="form-label">Todo Title</label>
										<input type="text" class="form-control">
									</div>											
								</div>
								<div class="col-12">
									<div class="mb-3">
										<label class="form-label">Assignee</label>
										<select class="select">
											<option>Choose</option>
											<option>Kathleen</option>											
											<option>Gifford</option>
										</select>
									</div>
								</div>
								<div class="col-6">
									<div class="mb-3">
										<label class="form-label">Tag</label>
										<select class="select">
											<option>Select</option>
											<option>pending</option>
											<option>Onhold</option>
											<option>Inprogress</option>
											<option>Done</option>
										</select>
									</div>
								</div>
								<div class="col-6">
									<div class="mb-3">
										<label class="form-label">Priority</label>
										<select class="select">
											<option>Select</option>
											<option>Medium</option>
											<option>High</option>
											<option>Low</option>
										</select>
									</div>
								</div>
								<div class="col-6">
									<div class="input-blocks todo-calendar">
										<label class="form-label">Due Date</label>                                                
										<div class="input-groupicon calender-input">
											<input type="text" class="form-control  datetimepicker" placeholder="Select">
										</div>
									</div>
								</div>
								<div class="col-6">
									<div class="mb-3">
										<label class="form-label">Status</label>
										<select class="select">
											<option>Select</option>
											<option>Active</option>
											<option>Inactive</option>
										</select>
									</div>
								</div>
								<div class="col-lg-12">
									<div class="mb-0 summer-description-box notes-summernote">
										<label class="form-label">Descriptions</label>
										<div id="summernote"></div>
										<p>Maximum 60 Characters</p>
									</div>											
								</div>
							</div>	
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn btn-primary">Submit</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Note -->

		<!-- Edit Note -->
		<div class="modal fade" id="edit-note-units">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Notes</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('notes')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-12">
									<div class="mb-3">
										<label class="form-label">Todo Title</label>
										<input type="text" class="form-control" value="Team meet at Starbucks">
									</div>											
								</div>
								<div class="col-12">
									<div class="mb-3">
										<label class="form-label">Assignee</label>
										<select class="select">
											<option>Choose</option>
											<option selected>Kathleen</option>			
											<option>Gifford</option>
										</select>
									</div>
								</div>
								<div class="col-6">
									<div class="mb-3">
										<label class="form-label">Tag</label>
										<select class="select">
											<option>Select</option>
											<option selected>pending</option>
											<option>Onhold</option>
											<option>Inprogress</option>
											<option>Done</option>
										</select>
									</div>
								</div>
								<div class="col-6">
									<div class="mb-3">
										<label class="form-label">Priority</label>
										<select class="select">
											<option>Select</option>
											<option selected>Medium</option>
											<option>High</option>
											<option>Low</option>
										</select>
									</div>
								</div>
								<div class="col-6">
									<div class="input-blocks todo-calendar">
										<label class="form-label">Due Date</label>                                                
										<div class="input-groupicon calender-input">
											<input type="text" class="form-control datetimepicker" placeholder="Select" value="13 Aug 1992">
										</div>
									</div>
								</div>
								<div class="col-6">
									<div class="mb-3">
										<label class="form-label">Status</label>
										<select class="select">
											<option>Select</option>
											<option selected>Active</option>
											<option>Inactive</option>
										</select>
									</div>
								</div>
								<div class="col-lg-12">
									<div class="mb-0 summer-description-box notes-summernote">
										<label class="form-label">Descriptions</label>
										<div id="summernote"></div>
										<p>Maximum 60 Characters</p>
									</div>											
								</div>
							</div>	
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Note -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('notes')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

		<!-- View Note -->
		<div class="modal fade" id="view-note-units">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="page-wrapper-new p-0">
						<div class="content">
							<div class="modal-header">
								<div class="d-flex align-items-center">
									<h4 class="modal-title me-3">Notes</h4>
									<p class="text-info">Personal</p>
								</div>
								<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
									<i class="ti ti-x"></i>
								</button>
							</div>
							<div class="modal-body">
								<div class="row">
									<div class="col-12">
										<div>
											<h4 class="mb-2">Meet Lisa to discuss project details</h4>
											<p>Hiking is a long, vigorous walk, usually on trails or footpaths in the countryside.
												Walking for pleasure developed in Europe during the eighteenth century.
												Religious pilgrimages have existed much longer but they involve walking long
												distances for a spiritual purpose associated with specific religions and also
												we achieve inner peace while we hike at a local park.</p>

												<p class="badge bg-outline-danger d-inline-flex align-items-center mb-0"><i class="fas fa-circle fs-6 me-1"></i> High</p>
										</div>	
									</div>
								</div>	
							</div>
							<div class="modal-footer">
								<a href="#" class="btn btn-danger" data-bs-dismiss="modal">Close</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- /View Note -->
@endif

@if (Route::is(['file-manager']))
    <!-- Files Toogle Slide -->
    <div class="toggle-sidebar">
        <div class="d-flex align-items-center justify-content-between head">
            <h4>File Preview</h4>
            <div class="d-flex align-items-center">
                <a href="javascript:void(0);" class="me-2 d-flex align-items-center"><i class="fa fa-star"></i></a>
                <a href="javascript:void(0);" class="me-2 d-flex align-items-center"><i data-feather="trash-2"
                        class="feather-16 text-center text-danger"></i></a>
                <a href="javascript:void(0);" class="sidebar-closes d-flex align-items-center" aria-hidden="true"><i
                        data-feather="x-circle" class="feather-26 color-primary"></i></a>
            </div>
        </div>
        <div class="text-center">
            <a href="javascript:void(0);"><img src="{{ URL::asset('build/img/file-manager/folder-lg.png') }}"
                    alt="Folder"></a>
            <h5>Website Backup for the Design team</h5>
            <p>File Size : 616 MB</p>
        </div>

        <div class="nav nav-tabs d-flex align-items-center justify-content-between py-4 mb-4" id="nav-tab"
            role="tablist">
            <a class="nav-link flex-fill active btn btn-light me-2 text-center" id="nav-home-tab"
                data-bs-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home"
                aria-selected="true"><i data-feather="list" class="feather-16 me-2 text-center"></i>Details</a>
            <a class="nav-link flex-fill btn btn-light" id="nav-profile-tab" data-bs-toggle="tab"
                href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="false"><i
                    data-feather="clock" class="feather-16 me-2"></i>Activity</a>
        </div>
        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                <h5 class="mb-4 d-flex align-items-center"><i data-feather="edit"
                        class="feather-20 me-2"></i>Properties</h5>
                <ul class="seprator-lg">
                    <li class="mb-4">
                        <h6>File Name</h6>
                        <p>Website Backup for the Designteam</p>
                    </li>
                    <li class="mb-4">
                        <h6>File Type</h6>
                        <p>Folder</p>
                    </li>
                    <li class="mb-4">
                        <h6>Size</h6>
                        <p>616 MB</p>
                    </li>
                    <li class="mb-4">
                        <h6>Created</h6>
                        <p>22 July 2023, 08:30 PM</p>
                    </li>
                    <li class="mb-4">
                        <h6>Location</h6>
                        <p class="location d-inline-flex align-items-center"><i data-feather="hard-drive"
                                class="feather-16 me-1"></i>Drive</p>
                    </li>
                    <li class="mb-4">
                        <h6>File Name</h6>
                        <p>23 July 2023, 08:30 PM</p>
                    </li>
                    <li class="mb-4">
                        <h6>Opened On</h6>
                        <p>28 July 2023, 06:40 PM</p>
                    </li>
                    <li>
                        <div class="row">
                            <!-- Editor -->
                            <div class="col-lg-12">
                                <div class="input-blocks summer-description-box transfer">
                                    <label>Description</label>
                                    <div id="summernote3">
                                    </div>
                                    <p>Maximum 60 Characters</p>
                                </div>
                            </div>
                            <!-- /Editor -->
                        </div>
                    </li>
                </ul>
                <h5 class="mb-4 d-flex align-items-center"><i data-feather="user" class="feather-20 me-2"></i>Who has
                    access</h5>
                <div class="d-flex align-items-center justify-content-between avatar-wrap">
                    <div class="avatar-access d-flex align-items-center mb-4">
                        <span>
                            <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right"
                                aria-label="Member 1" data-bs-original-title="Member 1"><img
                                    src="{{ URL::asset('build/img/profiles/avatar-01.jpg') }}" alt="Avatar"
                                    class="avatar-md"></a>
                        </span>
                        <span>
                            <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right"
                                aria-label="Member 2" data-bs-original-title="Member 2"><img
                                    src="{{ URL::asset('build/img/profiles/avatar-02.jpg') }}" alt="Avatar"
                                    class="avatar-md"></a>
                        </span>
                        <span>
                            <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right"
                                aria-label="Member 3" data-bs-original-title="Member 3"><img
                                    src="{{ URL::asset('build/img/profiles/avatar-03.jpg') }}" alt="Avatar"
                                    class="avatar-md"></a>
                        </span>
                        <span>
                            <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right"
                                aria-label="Member 4" data-bs-original-title="Member 4"><img
                                    src="{{ URL::asset('build/img/profiles/avatar-04.jpg') }}" alt="Avatar"
                                    class="avatar-md"></a>
                        </span>
                        <span>
                            <a href="javascript:void(0);"
                                class="avatar-md add d-flex align-items-center justify-content-center"><i
                                    data-feather="plus" class="feather-16 me-1"></i></a>
                        </span>
                    </div>
                </div>
                <p>Owned by Andrew. Shared with James, Fin, Davis</p>
            </div>
            <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                <h5 class="mb-4 d-flex align-items-center"><i data-feather="calendar"
                        class="feather-20 me-2"></i>This Week</h5>
                <ul class="mb-4">
                    <li class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <a href="javascript:void(0);" class="me-3"><img
                                    src="{{ URL::asset('build/img/profiles/avatar-01.jpg') }}" alt="Avatar"
                                    class="avatar-md"></a>
                            <p>Andrew commented on 1 items <br>3:39 PM Jul 19</p>
                        </div>
                        <p class="d-flex align-items-center location border-0"><img
                                src="{{ URL::asset('build/img/icons/folder.svg') }}" alt="Folder"
                                class="me-2">Website Backup for the Design team</p>
                    </li>
                    <li class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <a href="javascript:void(0);" class="me-3"><img
                                    src="{{ URL::asset('build/img/profiles/avatar-02.jpg') }}" alt="Avatar"
                                    class="avatar-md"></a>
                            <p>Drake shared an item<br>3:39 PM Jul 19</p>
                        </div>
                        <p class="d-flex align-items-center location border-0"><img
                                src="{{ URL::asset('build/img/icons/folder.svg') }}" alt="Folder"
                                class="me-2">Website Backup for the Design team</p>
                    </li>
                    <li class="mb-2">
                        <div class="d-flex align-items-center mb-2">
                            <a href="javascript:void(0);" class="me-3"><img
                                    src="{{ URL::asset('build/img/profiles/avatar-03.jpg') }}" alt="Avatar"
                                    class="avatar-md"></a>
                            <div>
                                <p class="mb-0 text-secondary">Melvin</p>
                                <p class="mb-0">Commentor</p>
                            </div>
                        </div>
                    </li>
                    <li class="mb-2">
                        <div class="d-flex align-items-center mb-2">
                            <a href="javascript:void(0);" class="me-3"><img
                                    src="{{ URL::asset('build/img/profiles/avatar-04.jpg') }}" alt="Avatar"
                                    class="avatar-md"></a>
                            <div>
                                <p class="mb-0 text-secondary">Drake</p>
                                <p class="mb-0">Editor</p>
                            </div>
                        </div>
                    </li>
                </ul>
                <h5 class="mb-4 d-flex align-items-center"><i data-feather="calendar"
                        class="feather-20 me-2"></i>Last Month</h5>
                <ul class="mb-4">
                    <li class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <a href="javascript:void(0);" class="me-3"><img
                                    src="{{ URL::asset('build/img/profiles/avatar-01.jpg') }}" alt="Avatar"
                                    class="avatar-md"></a>
                            <p>Andrew commented on 1 items <br>3:39 PM Jul 19</p>
                        </div>
                        <p class="d-flex align-items-center location border-0"><img
                                src="{{ URL::asset('build/img/icons/folder.svg') }}" alt="Folder"
                                class="me-2">Website Backup for the Design team</p>
                    </li>
                    <li class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <a href="javascript:void(0);" class="me-3"><img
                                    src="{{ URL::asset('build/img/profiles/avatar-02.jp') }}g" alt="Avatar"
                                    class="avatar-md"></a>
                            <p>Drake shared an item<br>3:39 PM Jul 19</p>
                        </div>
                        <p class="d-flex align-items-center location border-0"><img
                                src="{{ URL::asset('build/img/icons/folder.svg') }}" alt="Folder"
                                class="me-2">Website Backup for the Design team</p>
                    </li>
                    <li class="mb-2">
                        <div class="d-flex align-items-center mb-2">
                            <a href="javascript:void(0);" class="me-3"><img
                                    src="{{ URL::asset('build/img/profiles/avatar-03.jpg') }}" alt="Avatar"
                                    class="avatar-md"></a>
                            <div>
                                <p class="mb-0 text-secondary">Melvin</p>
                                <p class="mb-0">Commentor</p>
                            </div>
                        </div>
                    </li>
                    <li class="mb-2">
                        <div class="d-flex align-items-center mb-2">
                            <a href="javascript:void(0);" class="me-3"><img
                                    src="{{ URL::asset('build/img/profiles/avatar-04.jpg') }}" alt="Avatar"
                                    class="avatar-md"></a>
                            <div>
                                <p class="mb-0 text-secondary">Drake</p>
                                <p class="mb-0">Editor</p>
                            </div>
                        </div>
                    </li>
                </ul>
                <a href="javascript:void(0);" class="text-primary show-all"><i data-feather="plus-circle"
                        class="feather-20 me-2"></i>Show All</a>
            </div>
        </div>

    </div>
    <!-- Files Toogle Slide -->

    <!-- Upload File -->
    <div class="modal fade modal-default pos-modal upload-modal" id="upload-file" aria-labelledby="upload-file">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header p-4">
                    <h5>Upload File</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="drag-drop text-center mb-4">
                        <div class="upload">
                            <a href="#"><img src="{{ URL::asset('build/img/icons/drag-drop.svg') }}"
                                    alt=""></a>
                            <p>Drag and drop a <a href="#">file to upload</a></p>
                        </div>
                        <input type="file" multiple="">
                    </div>

                    <div class="d-flex align-items-center justify-content-between">
                        <p>3 of 1 files Uploaded</p>
                        <span>70%</span>
                    </div>
                    <div class="progress mt-2 mb-4">
                        <div class="progress-bar progress-bar bg-success" role="progressbar" style="width: 75%"
                            aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>

                    <ul>
                        <li class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center w-85">
                                <img src="{{ URL::asset('build/img/icons/folder.svg') }}" alt="Folder"
                                    class="me-2">
                                <div class="flex-fill">
                                    <h6><a href="javascript:void(0);">latest-version.zip<i data-feather="check-circle"
                                                class="ms-2 feather-16"></i></a></h6>
                                    <span>616 MB</span>
                                </div>
                            </div>
                            <a href="javascript:void(0);" class="text-danger text-right"><i data-feather="trash-2"
                                    class="feather-16"></i></a>
                        </li>
                        <li class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center w-85">
                                <img src="{{ URL::asset('build/img/icons/xls.svg') }}" alt="Folder"
                                    class="me-2">
                                <div class="flex-fill">
                                    <h6><a href="javascript:void(0);">Update work history.xls</a></h6>
                                    <span>616 MB</span>
                                    <div class="progress mt-2">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 75%"
                                            aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <a href="javascript:void(0);" class="text-danger me-2 d-flex align-items-center"><i
                                        data-feather="trash-2" class="feather-16"></i></a>
                                <a href="javascript:void(0);" class="text-default d-flex align-items-center"><i
                                        data-feather="pause-circle" class="feather-16"></i></a>
                            </div>
                        </li>
                        <li class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center w-85">
                                <img src="{{ URL::asset('build/img/icons/zip.svg') }}" alt="Folder"
                                    class="me-2">
                                <div class="flex-fill">
                                    <h6><a href="javascript:void(0);">Updated Project.zip</a></h6>
                                    <span>616 MB</span>
                                    <div class="progress mt-2">
                                        <div class="progress-bar" role="progressbar" aria-valuenow="0"
                                            aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <a href="javascript:void(0);" class="text-danger me-2 d-flex align-items-center"><i
                                        data-feather="trash-2" class="feather-16"></i></a>
                                <a href="javascript:void(0);" class="text-default d-flex align-items-center"><i
                                        data-feather="play-circle" class="feather-16"></i></a>
                            </div>
                        </li>
                    </ul>

                </div>
            </div>
        </div>
    </div>
    <!-- /Upload File -->

    <!-- Upload Folder -->
    <div class="modal fade modal-default pos-modal upload-modal" id="upload-folder" aria-labelledby="upload-folder">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header p-4">
                    <h5>Upload File</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="drag-drop text-center mb-4">
                        <div class="upload">
                            <a href="#"><img src="{{ URL::asset('build/img/icons/drag-drop.svg') }}"
                                    alt=""></a>
                            <p>Drag and drop a <a href="#">file to upload</a></p>
                        </div>
                        <input type="file" multiple="">
                    </div>

                    <div class="d-flex align-items-center justify-content-between">
                        <p>3 of 3 files Uploaded</p>
                        <span>100%</span>
                    </div>
                    <div class="progress mt-2 mb-4">
                        <div class="progress-bar progress-bar bg-success" role="progressbar" style="width: 100%"
                            aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>

                    <ul>
                        <li class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center w-85">
                                <img src="{{ URL::asset('build/img/icons/folder.svg') }}" alt="Folder"
                                    class="me-2">
                                <div class="flex-fill">
                                    <h6><a href="javascript:void(0);">latest-version<i data-feather="check-circle"
                                                class="ms-2 feather-16"></i></a></h6>
                                    <span>616 MB</span>
                                </div>
                            </div>
                            <a href="javascript:void(0);" class="text-danger text-right"><i data-feather="trash-2"
                                    class="feather-16"></i></a>
                        </li>
                        <li class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center w-85">
                                <img src="{{ URL::asset('build/img/icons/xls.svg') }}" alt="Folder"
                                    class="me-2">
                                <div class="flex-fill">
                                    <h6><a href="javascript:void(0);">Update work history.xls<i data-feather="trash-2"
                                                class="feather-16"></i></a></h6>
                                    <span>16 MB</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <a href="javascript:void(0);" class="text-danger me-2"><i data-feather="trash-2"
                                        class="feather-16"></i></a>
                                <a href="javascript:void(0);" class="text-default"><i data-feather="pause-circle"
                                        class="feather-16"></i></a>
                            </div>
                        </li>
                        <li class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center w-85">
                                <img src="{{ URL::asset('build/img/icons/zip.svg') }}" alt="Folder"
                                    class="me-2">
                                <div class="flex-fill">
                                    <h6><a href="javascript:void(0);">updated project.zip<i data-feather="trash-2"
                                                class="feather-16"></i></a></h6>
                                    <span>14 MB</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <a href="javascript:void(0);" class="text-danger me-2"><i data-feather="trash-2"
                                        class="feather-16"></i></a>
                                <a href="javascript:void(0);" class="text-default"><i data-feather="play-circle"
                                        class="feather-16"></i></a>
                            </div>
                        </li>
                    </ul>

                </div>
                <div class="modal-footer d-sm-flex justify-content-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Clear</button>
                    <button type="button" class="btn btn-primary">Upload</button>
                </div>
            </div>
        </div>
    </div>
    <!-- /Upload Folder -->

    <!-- Upload Folder -->
    <div class="modal fade modal-default pos-modal upload-message" id="upload-message"
        aria-labelledby="upload-message">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header p-4">
                    <h5>Upload File</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body p-4">

                    <div class="d-flex align-items-center justify-content-between">
                        <p>3 of 3 files Uploaded</p>
                        <span>100%</span>
                    </div>
                    <div class="progress mt-2 mb-4">
                        <div class="progress-bar progress-bar bg-success" role="progressbar" style="width: 100%"
                            aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="modal-footer d-sm-flex justify-content-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Clear</button>
                    <button type="button" class="btn btn-primary">Upload</button>
                </div>
            </div>
        </div>
    </div>
    <!-- /Upload Folder -->

    <!-- Create Folder -->
    <div class="modal fade modal-default pos-modal" id="create-folder" aria-labelledby="create-folder">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header p-4">
                    <h5>Create Folder</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label">Folder Name</label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer d-sm-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary">Create Folder</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Create Folder -->
@endif
@if (Route::is(['student-grid']))
<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('student-grid')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->

@endif
@if (Route::is(['parents']))
 <!-- Add Parent -->
 <div class="modal fade" id="add_parent">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Add Parent</h4>
          <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
            <i class="ti ti-x"></i>
          </button>
        </div>
        <form action="{{url('parents')}}">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12">
                <div class="d-flex align-items-center upload-pic flex-wrap row-gap-3 mb-3">
                  <div
                    class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                    <i class="ti ti-photo-plus fs-16"></i>
                  </div>
                  <div class="profile-upload">
                    <div class="profile-uploader d-flex align-items-center">
                      <div class="drag-upload-btn mb-3">
                        Upload
                        <input type="file" class="form-control image-sign" multiple>
                      </div>
                      <a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
                    </div>
                    <p>Upload image size 4MB, Format JPG, PNG, SVG</p>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Name</label>
                  <input type="text" class="form-control">
                </div>
                <div class="mb-3">
                  <label class="form-label">Phone Number</label>
                  <input type="text" class="form-control">
                </div>
                <div class="mb-3">
                  <label class="form-label">Email Address</label>
                  <input type="text" class="form-control">
                </div>
                <div class="mb-0">
                  <label class="form-label">Child</label>
                  <select class="select" multiple>
                    <option>Janet</option>
                    <option>Joann</option>
                    <option>Kathleen</option>
                    <option>Gifford</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
            <button type="submit" class="btn btn-primary">Add Parent</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- /Add Parent -->

  <!-- Edit Parent -->
  <div class="modal fade" id="edit_parent">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Edit Parent</h4>
          <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
            <i class="ti ti-x"></i>
          </button>
        </div>
        <form action="{{url('parents')}}">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12">
                <div class="d-flex align-items-center upload-pic flex-wrap row-gap-3 mb-3">
                  <div
                    class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                    <i class="ti ti-photo-plus fs-16"></i>
                  </div>
                  <div class="profile-upload">
                    <div class="profile-uploader d-flex align-items-center">
                      <div class="drag-upload-btn mb-3">
                        Upload
                        <input type="file" class="form-control image-sign" multiple>
                      </div>
                      <a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
                    </div>
                    <p>Upload image size 4MB, Format JPG, PNG, SVG</p>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Name</label>
                  <input type="text" class="form-control" placeholder="Enter Name" value="Thomas">
                </div>
                <div class="mb-3">
                  <label class="form-label">Phone Number</label>
                  <input type="text" class="form-control" placeholder="Enter Phone Number" value="+1 65738 58937">
                </div>
                <div class="mb-3">
                  <label class="form-label">Email Address</label>
                  <input type="text" class="form-control" placeholder="Enter Email Address"
                    value="thomas@example.com">
                </div>
                <div class="mb-0">
                  <label class="form-label">Child</label>
                  <select class="select" multiple>
                    <option selected>Janet</option>
                    <option selected>Joann</option>
                    <option>Kathleen</option>
                    <option>Gifford</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- /Edit Parent -->

  <!-- View Details -->
  <div class="modal fade" id="view_details">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">View Details</h4>
          <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
            <i class="ti ti-x"></i>
          </button>
        </div>
        <div class="modal-body mb-0">
          <div class="parent-wrap">
            <div class="row align-items-center">
              <div class="col-lg-6">
                <div class="d-flex align-items-center mb-3">
                  <span class="avatar avatar-xl me-2">
                    <img src="{{URL::asset('build/img/parents/parent-01.jpg')}}" alt="img">
                  </span>
                  <div class="parent-name">
                    <h5 class="mb-1">Thomas</h5>
                    <p>Added on 25 Mar 2024</p>
                  </div>
                </div>
              </div>
              <div class="col-lg-6">
                <ul class="d-flex align-items-center">
                  <li class="mb-3 me-5">
                    <p class="mb-1">Email</p>
                    <h6 class="fw-normal">tom@example.com</h6>
                  </li>
                  <li class="mb-3">
                    <p class="mb-1">Phone</p>
                    <h6 class="fw-normal">+1 65738 58937</h6>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <h5 class="mb-3">Children Details</h5>
          <div class="border rounded p-4 pb-1 mb-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap pb-1 mb-3 border-bottom">
              <span class="link-primary mb-2">AD9892434</span>
              <span class="badge badge-soft-success badge-md mb-2"><i
                  class="ti ti-circle-filled me-2"></i>Active</span>
            </div>
            <div class="d-flex align-items-center justify-content-between flex-wrap">
              <div class="d-flex align-items-center mb-3">
                <a href="{{url('student-details')}}" class="avatar"><img src="{{URL::asset('build/img/students/student-01.jpg')}}"
                    class="img-fluid rounded-circle" alt="img"></a>
                <div class="ms-2">
                  <p class="mb-0"><a href="{{url('student-details')}}">Janet</a></p>
                  <span>III, A</span>
                </div>
              </div>
              <ul class="d-flex align-items-center flex-wrap">
                <li class="mb-3 me-4">
                  <p class="mb-1">Roll No</p>
                  <h6 class="fw-normal">35010</h6>
                </li>
                <li class="mb-3 me-4">
                  <p class="mb-1">Gender</p>
                  <h6 class="fw-normal">Female</h6>
                </li>
                <li class="mb-3">
                  <p class="mb-1">Date of Joined</p>
                  <h6 class="fw-normal">22 Mar 2018</h6>
                </li>
              </ul>
              <div class="d-flex align-items-center">
                <a href="#" class="btn btn-light mb-3 me-3">Add Fees</a>
                <a href="{{url('student-details')}}" class="btn btn-primary mb-3">View Details</a>
              </div>
            </div>
          </div>
          <div class="border rounded p-4 pb-1 mb-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap pb-1 mb-3 border-bottom">
              <span class="link-primary mb-2">AD9892433</span>
              <span class="badge badge-soft-success badge-md mb-2"><i
                  class="ti ti-circle-filled me-2"></i>Active</span>
            </div>
            <div class="d-flex align-items-center justify-content-between flex-wrap">
              <div class="d-flex align-items-center mb-3">
                <a href="#" class="avatar"><img src="{{URL::asset('build/img/students/student-02.jpg')}}"
                    class="img-fluid rounded-circle" alt="img"></a>
                <div class="ms-2">
                  <p class="mb-0"><a href="{{url('student-details')}}">Joann</a></p>
                  <span>IV, B</span>
                </div>
              </div>
              <ul class="d-flex align-items-center flex-wrap">
                <li class="mb-3 me-4">
                  <p class="mb-1">Roll No</p>
                  <h6 class="fw-normal">35012</h6>
                </li>
                <li class="mb-3 me-4">
                  <p class="mb-1">Gender</p>
                  <h6 class="fw-normal">Male</h6>
                </li>
                <li class="mb-3">
                  <p class="mb-1">Date of Joined</p>
                  <h6 class="fw-normal">18 Mar 2018</h6>
                </li>
              </ul>
              <div class="d-flex align-items-center">
                <a href="#" class="btn btn-light mb-3 me-3">Add Fees</a>
                <a href="{{url('student-details')}}" class="btn btn-primary mb-3">View Details</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- /View Details -->

  <!-- Delete Modal -->
  <div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="{{url('parents')}}">
          <div class="modal-body text-center">
            <span class="delete-icon">
              <i class="ti ti-trash-x"></i>
            </span>
            <h4>Confirm Deletion</h4>
            <p>You want to delete all the marked items, this cant be undone once you delete.</p>
            <div class="d-flex justify-content-center">
              <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
              <button type="submit" class="btn btn-danger">Yes, Delete</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- /Delete Modal -->

 
@endif

@if (Route::is(['parent-grid']))
	<!-- Add Parent -->
    <div class="modal fade" id="add_parent">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Parent</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('parents')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex align-items-center upload-pic flex-wrap row-gap-3 mb-3">
                                    <div
                                        class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                                        <i class="ti ti-photo-plus fs-16"></i>
                                    </div>
                                    <div class="profile-upload">
                                        <div class="profile-uploader d-flex align-items-center">
                                            <div class="drag-upload-btn mb-3">
                                                Upload
                                                <input type="file" class="form-control image-sign" multiple="">
                                            </div>
                                            <a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
                                        </div>
                                        <p>Upload image size 4MB, Format JPG, PNG, SVG</p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="text" class="form-control">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Child</label>
                                    <select class="select" multiple>
                                        <option>Janet</option>
                                        <option>Joann</option>
                                        <option>Kathleen</option>
                                        <option>Gifford</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Parent</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Add Parent -->

    <!-- Edit Parent -->
    <div class="modal fade" id="edit_parent">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Parent</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('parents')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex align-items-center upload-pic flex-wrap row-gap-3 mb-3">
                                    <div
                                        class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                                        <i class="ti ti-photo-plus fs-16"></i>
                                    </div>
                                    <div class="profile-upload">
                                        <div class="profile-uploader d-flex align-items-center">
                                            <div class="drag-upload-btn mb-3">
                                                Upload
                                                <input type="file" class="form-control image-sign" multiple="">
                                            </div>
                                            <a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
                                        </div>
                                        <p>Upload image size 4MB, Format JPG, PNG, SVG</p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Name" value="Thomas">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" placeholder="Enter Phone Number"
                                        value="+1 65738 58937">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="text" class="form-control" placeholder="Enter Email Address"
                                        value="thomas@example.com">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Child</label>
                                    <select class="select" multiple>
                                        <option selected>Janet</option>
                                        <option selected>Joann</option>
                                        <option>Kathleen</option>
                                        <option>Gifford</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Parent -->

    <!-- View Details -->
    <div class="modal fade" id="view_details">
        <div class="modal-dialog modal-dialog-centered  modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">View Details</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body mb-0">
                    <div class="parent-wrap">
                        <div class="row align-items-center">
                            <div class="col-lg-6">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="avatar avatar-xl me-2">
                                        <img src="{{URL::asset('build/img/parents/parent-01.jpg')}}" alt="img">
                                    </span>
                                    <div class="parent-name">
                                        <h5 class="mb-1">Thomas</h5>
                                        <p>Added on 25 Mar 2024</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <ul class="d-flex align-items-center">
                                    <li class="mb-3 me-5">
                                        <p class="mb-1">Email</p>
                                        <h6 class="fw-normal">tom@example.com</h6>
                                    </li>
                                    <li class="mb-3">
                                        <p class="mb-1">Phone</p>
                                        <h6 class="fw-normal">+1 65738 58937</h6>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <h5 class="mb-3">Children Details</h5>
                    <div class="border rounded p-4 pb-1 mb-3">
                        <div
                            class="d-flex align-items-center justify-content-between flex-wrap pb-1 mb-3 border-bottom">
                            <span class="link-primary mb-2">AD9892434</span>
                            <span class="badge badge-soft-success badge-md mb-2"><i
                                    class="ti ti-circle-filled me-2"></i>Active</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div class="d-flex align-items-center mb-3">
                                <a href="{{url('student-details')}}" class="avatar"><img
                                        src="{{URL::asset('build/img/students/student-01.jpg')}}" class="img-fluid rounded-circle"
                                        alt="img"></a>
                                <div class="ms-2">
                                    <p class="mb-0"><a href="{{url('student-details')}}">Janet</a></p>
                                    <span>III, A</span>
                                </div>
                            </div>
                            <ul class="d-flex align-items-center flex-wrap">
                                <li class="mb-3 me-4">
                                    <p class="mb-1">Roll No</p>
                                    <h6 class="fw-normal">35010</h6>
                                </li>
                                <li class="mb-3 me-4">
                                    <p class="mb-1">Gender</p>
                                    <h6 class="fw-normal">Female</h6>
                                </li>
                                <li class="mb-3">
                                    <p class="mb-1">Date of Joined</p>
                                    <h6 class="fw-normal">22 Mar 2018</h6>
                                </li>
                            </ul>
                            <div class="d-flex align-items-center">
                                <a href="#" class="btn btn-light mb-3 me-3">Add Fees</a>
                                <a href="{{url('student-details')}}" class="btn btn-primary mb-3">View Details</a>
                            </div>
                        </div>
                    </div>
                    <div class="border rounded p-4 pb-1 mb-3">
                        <div
                            class="d-flex align-items-center justify-content-between flex-wrap pb-1 mb-3 border-bottom">
                            <span class="link-primary mb-2">AD9892433</span>
                            <span class="badge badge-soft-success badge-md mb-2"><i
                                    class="ti ti-circle-filled me-2"></i>Active</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div class="d-flex align-items-center mb-3">
                                <a href="{{url('student-details')}}" class="avatar"><img
                                        src="{{URL::asset('build/img/students/student-02.jpg')}}" class="img-fluid rounded-circle"
                                        alt="img"></a>
                                <div class="ms-2">
                                    <p class="mb-0"><a href="{{url('student-details')}}">Joann</a></p>
                                    <span>IV, B</span>
                                </div>
                            </div>
                            <ul class="d-flex align-items-center flex-wrap">
                                <li class="mb-3 me-4">
                                    <p class="mb-1">Roll No</p>
                                    <h6 class="fw-normal">35012</h6>
                                </li>
                                <li class="mb-3 me-4">
                                    <p class="mb-1">Gender</p>
                                    <h6 class="fw-normal">Male</h6>
                                </li>
                                <li class="mb-3">
                                    <p class="mb-1">Date of Joined</p>
                                    <h6 class="fw-normal">18 Mar 2018</h6>
                                </li>
                            </ul>
                            <div class="d-flex align-items-center">
                                <a href="#" class="btn btn-light mb-3 me-3">Add Fees</a>
                                <a href="{{url('student-details')}}" class="btn btn-primary mb-3">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /View Details -->

    <!-- Delete Modal -->
    <div class="modal fade" id="delete-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{url('parent-grid')}}">
                    <div class="modal-body text-center">
                        <span class="delete-icon">
                            <i class="ti ti-trash-x"></i>
                        </span>
                        <h4>Confirm Deletion</h4>
                        <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                        <div class="d-flex justify-content-center">
                            <a href="javascript:void(0);" class="btn btn-light me-3"
                                data-bs-dismiss="modal">Cancel</a>
                            <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Delete Modal -->

@endif

@if (Route::is(['guardians', 'guardian-grid']))
 <!-- Add Guardian -->
 <div class="modal fade" id="add_guardian">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Guardian</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('guardians')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center upload-pic flex-wrap row-gap-3 mb-3">
                                <div
                                    class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                                    <i class="ti ti-photo-plus fs-16"></i>
                                </div>
                                <div class="profile-upload">
                                    <div class="profile-uploader d-flex align-items-center">
                                        <div class="drag-upload-btn mb-3">
                                            Upload
                                            <input type="file" class="form-control image-sign" multiple>
                                        </div>
                                        <a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
                                    </div>
                                    <p>Upload image size 4MB, Format JPG, PNG, SVG</p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" >
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="text" class="form-control" >
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Child</label>
                                <select class="select" multiple>
                                    <option>Janet</option>
                                    <option>Joann</option>
                                    <option>Kathleen</option>
                                    <option>Gifford</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Guardian</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Guardian -->

<!-- Edit Guardian -->
<div class="modal fade" id="edit_guardian">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Guardian</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('guardians')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center upload-pic flex-wrap row-gap-3 mb-3">
                                <div
                                    class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                                    <i class="ti ti-photo-plus fs-16"></i>
                                </div>
                                <div class="profile-upload">
                                    <div class="profile-uploader d-flex align-items-center">
                                        <div class="drag-upload-btn mb-3">
                                            Upload
                                            <input type="file" class="form-control image-sign" multiple>
                                        </div>
                                        <a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
                                    </div>
                                    <p>Upload image size 4MB, Format JPG, PNG, SVG</p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" placeholder="Enter Name" value="Thomas">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" placeholder="Enter Phone Number"
                                    value="+1 65738 58937">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="text" class="form-control" placeholder="Enter Email Address"
                                    value="thomas@example.com">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Child</label>
                                <select class="select" multiple>
                                    <option selected>Janet</option>
                                    <option selected>Joann</option>
                                    <option>Kathleen</option>
                                    <option>Gifford</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Edit Guardian -->

<!-- View Details -->
<div class="modal fade" id="view_details">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">View Details</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="modal-body mb-0">
                <div class="parent-wrap">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center mb-3">
                                <span class="avatar avatar-xl me-2">
                                    <img src="{{URL::asset('build/img/guardians/guardian-01.jpg')}}" alt="img">
                                </span>
                                <div class="parent-name">
                                    <h5 class="mb-1">William</h5>
                                    <p>Added on 25 Mar 2024</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <ul class="d-flex align-items-center">
                                <li class="mb-3 me-5">
                                    <p class="mb-1">Email</p>
                                    <h6 class="fw-normal">wil@example.com</h6>
                                </li>
                                <li class="mb-3">
                                    <p class="mb-1">Phone</p>
                                    <h6 class="fw-normal">+1 82392 37359</h6>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <h5 class="mb-3">Children Details</h5>
                <div class="border rounded p-4 pb-1 mb-3">
                    <div
                        class="d-flex align-items-center justify-content-between flex-wrap pb-1 mb-3 border-bottom">
                        <span class="link-primary mb-2">AD9892434</span>
                        <span class="badge badge-soft-success badge-md mb-2"><i
                                class="ti ti-circle-filled me-2"></i>Active</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <div class="d-flex align-items-center mb-3">
                            <a href="{{url('student-details')}}" class="avatar"><img
                                    src="{{URL::asset('build/img/students/student-01.jpg')}}" class="img-fluid rounded-circle"
                                    alt="img"></a>
                            <div class="ms-2">
                                <p class="mb-0"><a href="{{url('student-details')}}">Janet</a></p>
                                <span>III, A</span>
                            </div>
                        </div>
                        <ul class="d-flex align-items-center flex-wrap">
                            <li class="mb-3 me-4">
                                <p class="mb-1">Roll No</p>
                                <h6 class="fw-normal">35010</h6>
                            </li>
                            <li class="mb-3 me-4">
                                <p class="mb-1">Gender</p>
                                <h6 class="fw-normal">Female</h6>
                            </li>
                            <li class="mb-3">
                                <p class="mb-1">Date of Joined</p>
                                <h6 class="fw-normal">22 Mar 2018</h6>
                            </li>
                        </ul>
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-light mb-3 me-3">Add Fees</a>
                            <a href="{{url('student-details')}}" class="btn btn-primary mb-3">View Details</a>
                        </div>
                    </div>
                </div>
                <div class="border rounded p-4 pb-1 mb-3">
                    <div
                        class="d-flex align-items-center justify-content-between flex-wrap pb-1 mb-3 border-bottom">
                        <span class="link-primary mb-2">AD9892433</span>
                        <span class="badge badge-soft-success badge-md mb-2"><i
                                class="ti ti-circle-filled me-2"></i>Active</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <div class="d-flex align-items-center mb-3">
                            <a href="{{url('student-details')}}" class="avatar"><img
                                    src="{{URL::asset('build/img/students/student-02.jpg')}}" class="img-fluid rounded-circle"
                                    alt="img"></a>
                            <div class="ms-2">
                                <p class="mb-0"><a href="{{url('student-details')}}">Joann</a></p>
                                <span>IV, B</span>
                            </div>
                        </div>
                        <ul class="d-flex align-items-center flex-wrap">
                            <li class="mb-3 me-4">
                                <p class="mb-1">Roll No</p>
                                <h6 class="fw-normal">35012</h6>
                            </li>
                            <li class="mb-3 me-4">
                                <p class="mb-1">Gender</p>
                                <h6 class="fw-normal">Male</h6>
                            </li>
                            <li class="mb-3">
                                <p class="mb-1">Date of Joined</p>
                                <h6 class="fw-normal">18 Mar 2018</h6>
                            </li>
                        </ul>
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-light mb-3 me-3">Add Fees</a>
                            <a href="{{url('student-details')}}" class="btn btn-primary mb-3">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /View Details -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('guardians')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you
                        delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->
@endif
@if (Route::is(['student-details']))
<!-- Login Details -->
<div class="modal fade" id="login_detail">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Login Details</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>		
            <div class="modal-body">		
                <div class="student-detail-info">
                    <span class="student-img"><img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="Img"></span>
                    <div class="name-info">
                        <h6>Janet <span>III, A</span></h6>
                    </div>
                </div>
                <div class="table-responsive custom-table no-datatable_length">
                    <table class="table datanew">
                        <thead class="thead-light">
                            <tr>
                                <th>User Type</th>
                                <th>User Name</th>
                                <th>Password </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Parent</td>
                                <td>parent53</td>
                                <td>parent@53</td>
                            </tr>
                            <tr>
                                <td>Student</td>
                                <td>student20</td>
                                <td>stdt@53</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>			
            <div class="modal-footer">
                <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>
<!-- /Login Details -->	

<!-- Add Fees Collect -->
<div class="modal fade" id="add_fees_collect">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <h4 class="modal-title">Collect Fees</h4>
                    <spa class="badge badge-sm bg-primary ms-2">AD124556</span>
                </div>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>		
            <form action="{{url('collect-fees')}}">
                <div class="modal-body">
                    <div class="bg-light-300 p-3 pb-0 rounded">
                        <div class="row align-items-center">
                            <div class="col-lg-3 col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <a href="{{url('student-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="img">
                                    </a>
                                    <a href="{{url('student-details')}}" class="d-flex flex-column"><span class="text-dark">Janet</span>III, A</a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="fs-12 mb-1">Total Outstanding</span>
                                    <p class="text-dark">2000</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="fs-12 mb-1">Last Date</span>
                                    <p class="text-dark">25 May 2024</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="badge badge-soft-danger"><i
                                    class="ti ti-circle-filled me-2"></i>Unpaid</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Fees Group</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Class 1 General</option>
                                    <option>Monthly Fees</option>
                                    <option>Admission-Fees</option>
                                    <option>Class 1- I Installment</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Fees Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Tuition Fees</option>
                                    <option>Monthly Fees</option>
                                    <option>Admission Fees</option>
                                    <option>Bus Fees</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="text" class="form-control" placeholder="Enter Amout">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Collection Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker"
                                        placeholder="Select">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Paytm</option>
                                    <option>Cash On Delivery</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Reference No</label>
                                <input type="text" class="form-control" placeholder="Enter Payment Reference No">
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-0">
                                <label class="form-label">Notes</label>
                                <textarea rows="4" class="form-control" placeholder="Add Notes"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Pay Fees</button>
                </div>
            </form>	
        </div>
    </div>
</div>
<!-- Add Fees Collect -->
@endif
@if (Route::is(['student-leaves']))
<!-- Apply Leave -->
<div class="modal fade" id="apply_leave">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Apply Leave</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('student-leaves')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Leave Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker"
                                        placeholder="15 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Leave Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Medical Leave</option>
                                    <option>Casual Leave</option>
                                    <option>Special Leave</option>
                                    <option>Maternity Leave</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Leave From date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker"
                                        placeholder="15 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Leave to Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker"
                                        placeholder="15 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Leave Days</label>
                                <div class="d-flex align-items-center check-radio-group">
                                    <label class="custom-radio">
                                        <input type="radio" name="radio" checked="">
                                        <span class="checkmark"></span>
                                        Full day
                                    </label>
                                    <label class="custom-radio">
                                        <input type="radio" name="radio">
                                        <span class="checkmark"></span>
                                        First Half
                                    </label>
                                    <label class="custom-radio">
                                        <input type="radio" name="radio">
                                        <span class="checkmark"></span>
                                        Second Half
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">No of Days</label>
                                <input type="text" class="form-control">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Reason</label>
                                <input type="text" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Apply Leave</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Apply Leave -->

<!-- Login Details -->
<div class="modal fade" id="login_detail">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Login Details</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="student-detail-info">
                    <span class="student-img"><img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="Img"></span>
                    <div class="name-info">
                        <h6>Janet <span>III, A</span></h6>
                    </div>
                </div>
                <div class="table-responsive custom-table no-datatable_length">
                    <table class="table datanew">
                        <thead class="thead-light">
                            <tr>
                                <th>User Type</th>
                                <th>User Name</th>
                                <th>Password </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Parent</td>
                                <td>parent53</td>
                                <td>parent@53</td>
                            </tr>
                            <tr>
                                <td>Student</td>
                                <td>student20</td>
                                <td>stdt@53</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>
<!-- /Login Details -->

<!-- Add Fees Collect -->
<div class="modal fade" id="add_fees_collect">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-wrapper">
                <div class="modal-header">
                    <div class="d-flex align-items-center">
                        <h4 class="modal-title">Collect Fees</h4>
                        <span class="badge badge-sm bg-primary ms-2">AD124556</span>
                    </div>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('student-leaves')}}">
                    <div class="modal-body">
                        <div class="bg-light-300 p-3 pb-0 rounded">
                            <div class="row align-items-center">
                                <div class="col-lg-3 col-md-6">
                                    <div class="d-flex align-items-center mb-3">
                                        <a href="{{url('student-details')}}" class="avatar avatar-md me-2">
                                            <img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="img">
                                        </a>
                                        <a href="{{url('student-details')}}" class="d-flex flex-column"><span class="text-dark">Janet</span>III, A</a>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="mb-3">
                                        <span class="fs-12 mb-1">Total Outstanding</span>
                                        <p class="text-dark">2000</p>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="mb-3">
                                        <span class="fs-12 mb-1">Last Date</span>
                                        <p class="text-dark">25 May 2024</p>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="mb-3">
                                        <span class="badge badge-soft-danger"><i
                                        class="ti ti-circle-filled me-2"></i>Unpaid</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Fees Group</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Class 1 General</option>
                                        <option>Monthly Fees</option>
                                        <option>Admission-Fees</option>
                                        <option>Class 1- I Installment</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Fees Type</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Tuition Fees</option>
                                        <option>Monthly Fees</option>
                                        <option>Admission Fees</option>
                                        <option>Bus Fees</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Amount</label>
                                    <input type="text" class="form-control" placeholder="Enter Amout">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Collection Date</label>
                                    <div class="date-pic">
                                        <input type="text" class="form-control datetimepicker"
                                            placeholder="Select">
                                        <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Type</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Paytm</option>
                                        <option>Cash On Delivery</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Payment Reference No</label>
                                    <input type="text" class="form-control"
                                        placeholder="Enter Payment Reference No">
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div
                                    class="modal-satus-toggle d-flex align-items-center justify-content-between mb-3">
                                    <div class="status-title">
                                        <h5>Status</h5>
                                        <p>Change the Status by toggle </p>
                                    </div>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user1" class="check">
                                        <label for="user1" class="checktoggle"> </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="mb-0">
                                    <label class="form-label">Notes</label>
                                    <textarea rows="4" class="form-control" placeholder="Add Notes"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Pay Fees</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Apply Leave -->
</div>
<!-- /Main Wrapper -->
@endif
@if (Route::is(['student-fees']))

<!-- Login Details -->
<div class="modal fade" id="login_detail">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Login Details</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="student-detail-info">
                    <span class="student-img"><img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="Img"></span>
                    <div class="name-info">
                        <h6>Janet <span>III, A</span></h6>
                    </div>
                </div>
                <div class="table-responsive custom-table no-datatable_length">
                    <table class="table datanew">
                        <thead class="thead-light">
                            <tr>
                                <th>User Type</th>
                                <th>User Name</th>
                                <th>Password </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Parent</td>
                                <td>parent53</td>
                                <td>parent@53</td>
                            </tr>
                            <tr>
                                <td>Student</td>
                                <td>student20</td>
                                <td>stdt@53</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>
<!-- /Login Details -->

<!-- Add Fees Collect -->
<div class="modal fade" id="add_fees_collect">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <h4 class="modal-title">Collect Fees</h4>
                    <spa class="badge badge-sm bg-primary ms-2">AD124556</span>
                </div>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('collect-fees')}}">
                <div class="modal-body">
                    <div class="bg-light-300 p-3 pb-0 rounded">
                        <div class="row align-items-center">
                            <div class="col-lg-3 col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <a href="{{url('student-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="img">
                                    </a>
                                    <a href="{{url('student-details')}}" class="d-flex flex-column"><span class="text-dark">Janet</span>III, A</a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="fs-12 mb-1">Total Outstanding</span>
                                    <p class="text-dark">2000</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="fs-12 mb-1">Last Date</span>
                                    <p class="text-dark">25 May 2024</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="badge badge-soft-danger"><i
                                    class="ti ti-circle-filled me-2"></i>Unpaid</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Fees Group</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Class 1 General</option>
                                    <option>Monthly Fees</option>
                                    <option>Admission-Fees</option>
                                    <option>Class 1- I Installment</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Fees Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Tuition Fees</option>
                                    <option>Monthly Fees</option>
                                    <option>Admission Fees</option>
                                    <option>Bus Fees</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="text" class="form-control" placeholder="Enter Amout">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Collection Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="Select">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Paytm</option>
                                    <option>Cash On Delivery</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Reference No</label>
                                <input type="text" class="form-control"
                                    placeholder="Enter Payment Reference No">
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-0">
                                <label class="form-label">Notes</label>
                                <textarea rows="4" class="form-control" placeholder="Add Notes"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Pay Fees</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Fees Collect -->
       @endif
@if (Route::is(['student-library']))
<!-- Login Details -->
<div class="modal fade" id="login_detail">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Login Details</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>		
            <div class="modal-body">		
                <div class="student-detail-info">
                    <span class="student-img"><img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="Img"></span>
                    <div class="name-info">
                        <h6>Janet <span>III, A</span></h6>
                    </div>
                </div>
                <div class="table-responsive custom-table no-datatable_length">
                    <table class="table datanew">
                        <thead class="thead-light">
                            <tr>
                                <th>User Type</th>
                                <th>User Name</th>
                                <th>Password </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Parent</td>
                                <td>parent53</td>
                                <td>parent@53</td>
                            </tr>
                            <tr>
                                <td>Student</td>
                                <td>student20</td>
                                <td>stdt@53</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>			
            <div class="modal-footer">
                <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>
<!-- /Login Details -->	

<!-- Add Fees Collect -->
<div class="modal fade" id="add_fees_collect">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <h4 class="modal-title">Collect Fees</h4>
                    <span class="badge badge-sm bg-primary ms-2">AD124556</span>
                </div>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>		
            <form action="{{url('collect-fees')}}">
                <div class="modal-body">
                    <div class="bg-light-300 p-3 pb-0 rounded">
                        <div class="row align-items-center">
                            <div class="col-lg-3 col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <a href="{{url('student-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="img">
                                    </a>
                                    <a href="{{url('student-details')}}" class="d-flex flex-column"><span class="text-dark">Janet</span>III, A</a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="fs-12 mb-1">Total Outstanding</span>
                                    <p class="text-dark">2000</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="fs-12 mb-1">Last Date</span>
                                    <p class="text-dark">25 May 2024</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="badge badge-soft-danger"><i
                                    class="ti ti-circle-filled me-2"></i>Unpaid</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Fees Group</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Class 1 General</option>
                                    <option>Monthly Fees</option>
                                    <option>Admission-Fees</option>
                                    <option>Class 1- I Installment</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Fees Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Tuition Fees</option>
                                    <option>Monthly Fees</option>
                                    <option>Admission Fees</option>
                                    <option>Bus Fees</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="text" class="form-control" placeholder="Enter Amout">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Collection Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker"
                                        placeholder="Select">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Paytm</option>
                                    <option>Cash On Delivery</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Reference No</label>
                                <input type="text" class="form-control" placeholder="Enter Payment Reference No">
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-0">
                                <label class="form-label">Notes</label>
                                <textarea rows="4" class="form-control" placeholder="Add Notes"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Pay Fees</button>
                </div>
            </form>	
        </div>
    </div>
</div>
<!-- Add Fees Collect -->
@endif
@if (Route::is(['student-result']))
<!-- Login Details -->
<div class="modal fade" id="login_detail">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Login Details</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="student-detail-info">
                    <span class="student-img"><img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="Img"></span>
                    <div class="name-info">
                        <h6>Janet <span>III, A</span></h6>
                    </div>
                </div>
                <div class="table-responsive custom-table no-datatable_length">
                    <table class="table datanew">
                        <thead class="thead-light">
                            <tr>
                                <th>User Type</th>
                                <th>User Name</th>
                                <th>Password </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Parent</td>
                                <td>parent53</td>
                                <td>parent@53</td>
                            </tr>
                            <tr>
                                <td>Student</td>
                                <td>student20</td>
                                <td>stdt@53</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>
<!-- /Login Details -->

<!-- Add Fees Collect -->
<div class="modal fade" id="add_fees_collect">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <h4 class="modal-title">Collect Fees</h4>
                    <span class="badge badge-sm bg-primary ms-2">AD124556</span>
                </div>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('collect-fees')}}">
                <div class="modal-body">
                    <div class="bg-light-300 p-3 pb-0 rounded">
                        <div class="row align-items-center">
                            <div class="col-lg-3 col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <a href="{{url('student-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="img">
                                    </a>
                                    <a href="{{url('student-details')}}" class="d-flex flex-column"><span class="text-dark">Janet</span>III, A</a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="fs-12 mb-1">Total Outstanding</span>
                                    <p class="text-dark">2000</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="fs-12 mb-1">Last Date</span>
                                    <p class="text-dark">25 May 2024</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="badge badge-soft-danger"><i
                                    class="ti ti-circle-filled me-2"></i>Unpaid</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Fees Group</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Class 1 General</option>
                                    <option>Monthly Fees</option>
                                    <option>Admission-Fees</option>
                                    <option>Class 1- I Installment</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Fees Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Tuition Fees</option>
                                    <option>Monthly Fees</option>
                                    <option>Admission Fees</option>
                                    <option>Bus Fees</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="text" class="form-control" placeholder="Enter Amout">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Collection Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="Select">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Paytm</option>
                                    <option>Cash On Delivery</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Reference No</label>
                                <input type="text" class="form-control"
                                    placeholder="Enter Payment Reference No">
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="switch-sm2">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-0">
                                <label class="form-label">Notes</label>
                                <textarea rows="4" class="form-control" placeholder="Add Notes"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Pay Fees</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Fees Collect -->
@endif
@if (Route::is(['student-time-table']))
   <!-- Login Details -->
		<div class="modal fade" id="login_detail">
			<div class="modal-dialog modal-dialog-centered  modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Login Details</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>		
					<div class="modal-body">		
						<div class="student-detail-info">
							<span class="student-img"><img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="Img"></span>
							<div class="name-info">
								<h6>Janet <span>III, A</span></h6>
							</div>
						</div>
						<div class="table-responsive custom-table no-datatable_length">
							<table class="table datanew">
								<thead class="thead-light">
									<tr>
										<th>User Type</th>
										<th>User Name</th>
										<th>Password </th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>Parent</td>
										<td>parent53</td>
										<td>parent@53</td>
									</tr>
									<tr>
										<td>Student</td>
										<td>student20</td>
										<td>stdt@53</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>			
					<div class="modal-footer">
						<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
					</div>
				</div>
			</div>
		</div>
		<!-- /Login Details -->	

		<!-- Add Fees Collect -->
		<div class="modal fade" id="add_fees_collect">
			<div class="modal-dialog modal-dialog-centered  modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<div class="d-flex align-items-center">
							<h4 class="modal-title">Collect Fees</h4>
							<span class="badge badge-sm bg-primary ms-2">AD124556</span>
						</div>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>		
					<form action="{{url('collect-fees')}}">
						<div class="modal-body">
							<div class="bg-light-300 p-3 pb-0 rounded">
								<div class="row align-items-center">
									<div class="col-lg-3 col-md-6">
										<div class="d-flex align-items-center mb-3">
											<a href="{{url('student-details')}}" class="avatar avatar-md me-2">
												<img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="img">
											</a>
											<a href="{{url('student-details')}}" class="d-flex flex-column"><span class="text-dark">Janet</span>III, A</a>
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<span class="fs-12 mb-1">Total Outstanding</span>
											<p class="text-dark">2000</p>
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<span class="fs-12 mb-1">Last Date</span>
											<p class="text-dark">25 May 2024</p>
										</div>
									</div>
									<div class="col-lg-3 col-md-6">
										<div class="mb-3">
											<span class="badge badge-soft-danger"><i
											class="ti ti-circle-filled me-2"></i>Unpaid</span>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-lg-6">
									<div class="mb-3">
										<label class="form-label">Fees Group</label>
										<select class="select">
											<option>Select</option>
											<option>Class 1 General</option>
											<option>Monthly Fees</option>
											<option>Admission-Fees</option>
											<option>Class 1- I Installment</option>
										</select>
									</div>
								</div>
								<div class="col-lg-6">
									<div class="mb-3">
										<label class="form-label">Fees Type</label>
										<select class="select">
											<option>Select</option>
											<option>Tuition Fees</option>
											<option>Monthly Fees</option>
											<option>Admission Fees</option>
											<option>Bus Fees</option>
										</select>
									</div>
								</div>
								<div class="col-lg-6">
									<div class="mb-3">
										<label class="form-label">Amount</label>
										<input type="text" class="form-control" placeholder="Enter Amout">
									</div>
								</div>
								<div class="col-lg-6">
									<div class="mb-3">
										<label class="form-label">Collection Date</label>
										<div class="date-pic">
											<input type="text" class="form-control datetimepicker"
												placeholder="Select">
											<span class="cal-icon"><i class="ti ti-calendar"></i></span>
										</div>
									</div>
								</div>
								<div class="col-lg-6">
									<div class="mb-3">
										<label class="form-label">Payment Type</label>
										<select class="select">
											<option>Select</option>
											<option>Paytm</option>
											<option>Cash On Delivery</option>
										</select>
									</div>
								</div>
								<div class="col-lg-6">
									<div class="mb-3">
										<label class="form-label">Payment Reference No</label>
										<input type="text" class="form-control" placeholder="Enter Payment Reference No">
									</div>
								</div>
								<div class="col-lg-12">
									<div class="modal-satus-toggle d-flex align-items-center justify-content-between mb-3">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user1" class="check">
											<label for="user1" class="checktoggle"> </label>
										</div>
									</div>
								</div>
								<div class="col-lg-12">
									<div class="mb-0">
										<label class="form-label">Notes</label>
										<textarea rows="4" class="form-control" placeholder="Add Notes"></textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Pay Fees</button>
						</div>
					</form>	
				</div>
			</div>
		</div>
		<!-- Add Fees Collect -->
@endif
@if (Route::is(['teacher-grid']))
<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('parent-grid')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->
@endif
@if (Route::is(['teachers']))
<!-- Login Details -->
<div class="modal fade" id="login_detail">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Login Details</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>		
            <div class="modal-body">		
                <div class="student-detail-info">
                    <span class="student-img"><img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" alt="img"></span>
                    <div class="name-info">
                        <h6>Teresa <span>III, A</span></h6>
                    </div>
                </div>
                <div class="table-responsive custom-table no-datatable_length">
                    <table class="table datanew">
                        <thead class="thead-light">
                            <tr>
                                <th>User Type</th>
                                <th>User Name</th>
                                <th>Password </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Teacher</td>
                                <td>teacher20</td>
                                <td>teacher@53</td>
                            </tr>
                            <tr>
                                <td>Parent</td>
                                <td>parent53</td>
                                <td>parent@53</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>			
            <div class="modal-footer">
                <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>
<!-- /Login Details -->	

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('teachers')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->
@endif
@if (Route::is(['teacher-details']))
<!-- Login Details -->
<div class="modal fade" id="login_detail">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Login Details</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>		
            <div class="modal-body">		
                <div class="student-detail-info">
                    <span class="student-img"><img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" alt="img"></span>
                    <div class="name-info">
                        <h6>Teresa <span>III, A</span></h6>
                    </div>
                </div>
                <div class="table-responsive custom-table no-datatable_length">
                    <table class="table datanew">
                        <thead class="thead-light">
                            <tr>
                                <th>User Type</th>
                                <th>User Name</th>
                                <th>Password </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Teacher</td>
                                <td>teacher20</td>
                                <td>teacher@53</td>
                            </tr>
                            <tr>
                                <td>Parent</td>
                                <td>parent53</td>
                                <td>parent@53</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>			
            <div class="modal-footer">
                <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>
<!-- /Login Details -->	
@endif
@if (Route::is(['teacher-library']))
<!-- Login Details -->
<div class="modal fade" id="login_detail">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Login Details</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>		
            <div class="modal-body">		
                <div class="student-detail-info">
                    <span class="student-img"><img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" alt="img"></span>
                    <div class="name-info">
                        <h6>Teresa <span>III, A</span></h6>
                    </div>
                </div>
                <div class="table-responsive custom-table no-datatable_length">
                    <table class="table datanew">
                        <thead class="thead-light">
                            <tr>
                                <th>User Type</th>
                                <th>User Name</th>
                                <th>Password </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Teacher</td>
                                <td>teacher20</td>
                                <td>teacher@53</td>
                            </tr>
                            <tr>
                                <td>Parent</td>
                                <td>parent53</td>
                                <td>parent@53</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>			
            <div class="modal-footer">
                <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>
<!-- /Login Details -->	
@endif
@if (Route::is(['teacher-salary']))
	<!-- Apply Leave -->
	<div class="modal fade" id="apply_leave">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Apply Leave</h4>
					<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
						<i class="ti ti-x"></i>
					</button>
				</div>
				<form action="{{url('student-leaves')}}">
					<div class="modal-body">
						<div class="row">
							<div class="col-md-12">
								<div class="mb-3">
									<label class="form-label">Leave Date</label>
									<div class="date-pic">
										<input type="text" class="form-control datetimepicker"
											placeholder="15 May 2024">
										<span class="cal-icon"><i class="ti ti-calendar"></i></span>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">Leave Type</label>
									<select class="select">
										<option>Select</option>
										<option>Medical Leave</option>
										<option>Casual Leave</option>
										<option>Special Leave</option>
										<option>Maternity Leave</option>
									</select>
								</div>
								<div class="mb-3">
									<label class="form-label">Leave From date</label>
									<div class="date-pic">
										<input type="text" class="form-control datetimepicker"
											placeholder="15 May 2024">
										<span class="cal-icon"><i class="ti ti-calendar"></i></span>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">Leave to Date</label>
									<div class="date-pic">
										<input type="text" class="form-control datetimepicker"
											placeholder="15 May 2024">
										<span class="cal-icon"><i class="ti ti-calendar"></i></span>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">Leave Days</label>
									<div class="d-flex align-items-center check-radio-group">
										<label class="custom-radio">
											<input type="radio" name="radio" checked="">
											<span class="checkmark"></span>
											Full day
										</label>
										<label class="custom-radio">
											<input type="radio" name="radio">
											<span class="checkmark"></span>
											First Half
										</label>
										<label class="custom-radio">
											<input type="radio" name="radio">
											<span class="checkmark"></span>
											Second Half
										</label>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">No of Days</label>
									<input type="text" class="form-control">
								</div>
								<div class="mb-0">
									<label class="form-label">Reason</label>
									<input type="text" class="form-control">
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
						<button type="submit" class="btn btn-primary">Apply Leave</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<!-- /Apply Leave -->

	<!-- Login Details -->
	<div class="modal fade" id="login_detail">
		<div class="modal-dialog modal-dialog-centered  modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Login Details</h4>
					<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
						<i class="ti ti-x"></i>
					</button>
				</div>
				<div class="modal-body">
					<div class="student-detail-info">
						<span class="student-img"><img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="Img"></span>
						<div class="name-info">
							<h6>Janet <span>III, A</span></h6>
						</div>
					</div>
					<div class="table-responsive custom-table no-datatable_length">
						<table class="table datanew">
							<thead class="thead-light">
								<tr>
									<th>User Type</th>
									<th>User Name</th>
									<th>Password </th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>Parent</td>
									<td>parent53</td>
									<td>parent@53</td>
								</tr>
								<tr>
									<td>Student</td>
									<td>student20</td>
									<td>stdt@53</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<div class="modal-footer">
					<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
				</div>
			</div>
		</div>
	</div>
	<!-- /Login Details -->
@endif
@if (Route::is(['routine-teachers']))
  <!-- Apply Leave -->
	<div class="modal fade" id="apply_leave">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Apply Leave</h4>
					<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
						<i class="ti ti-x"></i>
					</button>
				</div>
				<form action="{{url('student-leaves')}}">
					<div class="modal-body">
						<div class="row">
							<div class="col-md-12">
								<div class="mb-3">
									<label class="form-label">Leave Date</label>
									<div class="date-pic">
										<input type="text" class="form-control datetimepicker"
											placeholder="15 May 2024">
										<span class="cal-icon"><i class="ti ti-calendar"></i></span>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">Leave Type</label>
									<select class="select">
										<option>Select</option>
										<option>Medical Leave</option>
										<option>Casual Leave</option>
										<option>Special Leave</option>
										<option>Maternity Leave</option>
									</select>
								</div>
								<div class="mb-3">
									<label class="form-label">Leave From date</label>
									<div class="date-pic">
										<input type="text" class="form-control datetimepicker"
											placeholder="15 May 2024">
										<span class="cal-icon"><i class="ti ti-calendar"></i></span>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">Leave to Date</label>
									<div class="date-pic">
										<input type="text" class="form-control datetimepicker"
											placeholder="15 May 2024">
										<span class="cal-icon"><i class="ti ti-calendar"></i></span>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">Leave Days</label>
									<div class="d-flex align-items-center check-radio-group">
										<label class="custom-radio">
											<input type="radio" name="radio" checked="">
											<span class="checkmark"></span>
											Full day
										</label>
										<label class="custom-radio">
											<input type="radio" name="radio">
											<span class="checkmark"></span>
											First Half
										</label>
										<label class="custom-radio">
											<input type="radio" name="radio">
											<span class="checkmark"></span>
											Second Half
										</label>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">No of Days</label>
									<input type="text" class="form-control">
								</div>
								<div class="mb-0">
									<label class="form-label">Reason</label>
									<input type="text" class="form-control">
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
						<button type="submit" class="btn btn-primary">Apply Leave</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<!-- /Apply Leave -->

	<!-- Login Details -->
	<div class="modal fade" id="login_detail">
		<div class="modal-dialog modal-dialog-centered  modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Login Details</h4>
					<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
						<i class="ti ti-x"></i>
					</button>
				</div>
				<div class="modal-body">
					<div class="student-detail-info">
						<span class="student-img"><img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="Img"></span>
						<div class="name-info">
							<h6>Janet <span>III, A</span></h6>
						</div>
					</div>
					<div class="table-responsive custom-table no-datatable_length">
						<table class="table datanew">
							<thead class="thead-light">
								<tr>
									<th>User Type</th>
									<th>User Name</th>
									<th>Password </th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>Parent</td>
									<td>parent53</td>
									<td>parent@53</td>
								</tr>
								<tr>
									<td>Student</td>
									<td>student20</td>
									<td>stdt@53</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<div class="modal-footer">
					<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
				</div>
			</div>
		</div>
	</div>
	<!-- /Login Details -->
@endif
@if (Route::is(['students']))
<!-- Login Details -->
<div class="modal fade" id="login_detail">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Login Details</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>		
            <div class="modal-body">		
                <div class="student-detail-info">
                    <span class="student-img"><img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="Img"></span>
                    <div class="name-info">
                        <h6>Janet <span>III, A</span></h6>
                    </div>
                </div>
                <div class="table-responsive custom-table no-datatable_length">
                    <table class="table datanew">
                        <thead class="thead-light">
                            <tr>
                                <th>User Type</th>
                                <th>User Name</th>
                                <th>Password </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Parent</td>
                                <td>parent53</td>
                                <td>parent@53</td>
                            </tr>
                            <tr>
                                <td>Student</td>
                                <td>student20</td>
                                <td>stdt@53</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>			
            <div class="modal-footer">
                <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>
<!-- /Login Details -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('students')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->

<!-- Add Fees Collect -->
<div class="modal fade" id="add_fees_collect">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <h4 class="modal-title">Collect Fees</h4>
                    <span class="badge badge-sm bg-primary ms-2">AD124556</span>
                </div>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('students')}}">
                <div class="modal-body">
                    <div class="bg-light-300 p-3 pb-0 rounded mb-4">
                        <div class="row align-items-center">
                            <div class="col-lg-3 col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <a href="{{url('student-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="img">
                                    </a>
                                    <a href="{{url('student-details')}}" class="d-flex flex-column"><span class="text-dark">Janet</span>III, A</a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="fs-12 mb-1">Total Outstanding</span>
                                    <p class="text-dark">2000</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="fs-12 mb-1">Last Date</span>
                                    <p class="text-dark">25 May 2024</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="badge badge-soft-danger"><i
                                    class="ti ti-circle-filled me-2"></i>Unpaid</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Fees Group</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Class 1 General</option>
                                    <option>Monthly Fees</option>
                                    <option>Admission-Fees</option>
                                    <option>Class 1- I Installment</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Fees Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Tuition Fees</option>
                                    <option>Monthly Fees</option>
                                    <option>Admission Fees</option>
                                    <option>Bus Fees</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="text" class="form-control" placeholder="Enter Amout">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Collection Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="Select">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Paytm</option>
                                    <option>Cash On Delivery</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Reference No</label>
                                <input type="text" class="form-control"
                                    placeholder="Enter Payment Reference No">
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div
                                class="modal-satus-toggle d-flex align-items-center justify-content-between mb-3">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="status-toggle modal-status">
                                    <input type="checkbox" id="user1" class="check">
                                    <label for="user1" class="checktoggle"> </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-0">
                                <label class="form-label">Notes</label>
                                <textarea rows="4" class="form-control" placeholder="Add Notes"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Pay Fees</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Fees Collect -->
	@endif
@if (Route::is(['class-time-table']))
<!-- Add Class Time Table -->
<div class="modal fade" id="add_time_table">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Time Table</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('class-time-table')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label">Class</label>
                                <input type="text" class="form-control" placeholder="Enter Class">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label">Section</label>
                                <select class="select">
                                    <option>A</option>
                                    <option>B</option>
                                    <option>C</option>
                                    <option>D</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label">Subject Group</label>
                                <select class="select">
                                    <option>Class I</option>
                                    <option>Class II</option>
                                    <option>Class III</option>
                                    <option>Class IV</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label">Period Start Time</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>09:00 AM</option>
                                    <option>09:45 AM</option>
                                    <option>10:45 AM</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label">Duration(min)</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>45 Mins</option>
                                    <option>1 Hour</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="add-more-timetable">
                        <ul class="tab-links nav nav-pills" id="pills-tab2" role="tablist">
                            <li class="nav-link active" id="pills-monday-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-monday" role="tab" aria-controls="pills-monday"
                                aria-selected="true"><a href="javascript:void(0);">Monday</a></li>
                            <li class="nav-link" id="pills-tuesday-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-tuesday" role="tab" aria-controls="pills-tuesday"
                                aria-selected="false" tabindex="-1"><a href="javascript:void(0);">Tuesday</a></li>
                            <li class="nav-link" id="pills-wednesday-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-wednesday" role="tab" aria-controls="pills-wednesday"
                                aria-selected="false" tabindex="-1"><a href="javascript:void(0);">Wednesday</a></li>
                            <li class="nav-link" id="pills-thursday-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-thursday" role="tab" aria-controls="pills-thursday"
                                aria-selected="false" tabindex="-1"><a href="javascript:void(0);">Thursday</a></li>
                            <li class="nav-link" id="pills-friday-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-friday" role="tab" aria-controls="pills-friday"
                                aria-selected="false" tabindex="-1"><a href="javascript:void(0);">Friday</a></li>
                        </ul>
                        <div class="tab-content pt-0 dashboard-tab">
                            <div class="tab-pane fade show active" id="pills-monday" role="tabpanel"
                                aria-labelledby="pills-monday-tab">
                                <div class="add-timetable-row">
                                    <div class="row timetable-count">
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Subject</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>English</option>
                                                    <option>Spanish</option>
                                                    <option>Physics</option>
                                                    <option>Maths</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Teacher</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Hellana</option>
                                                    <option>Erickson</option>
                                                    <option>Teresa</option>
                                                    <option>Aaron</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Time From</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>09:00</option>
                                                    <option>09:45</option>
                                                    <option>10:45</option>
                                                    <option>11:30</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="d-flex align-items-end">
                                                <div class="mb-3 flex-fill">
                                                    <label class="form-label">Time To</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>09:45</option>
                                                        <option>10:45</option>
                                                        <option>11:30</option>
                                                        <option>12:15</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3 ms-2">
                                                    <a href="#" class="delete-time-table"><i
                                                            class="ti ti-trash"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="javascript:void(0);" class="btn btn-primary add-new-timetable"><i
                                            class="ti ti-square-rounded-plus-filled me-2"></i>Add New</a>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pills-tuesday" role="tabpanel"
                                aria-labelledby="pills-tuesday-tab">
                                <div class="add-timetable-row">
                                    <div class="row timetable-count">
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Subject</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>English</option>
                                                    <option>Spanish</option>
                                                    <option>Physics</option>
                                                    <option>Maths</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Teacher</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Hellana</option>
                                                    <option>Erickson</option>
                                                    <option>Teresa</option>
                                                    <option>Aaron</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Time From</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>09:00</option>
                                                    <option>09:45</option>
                                                    <option>10:45</option>
                                                    <option>11:30</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="d-flex align-items-end">
                                                <div class="mb-3 flex-fill">
                                                    <label class="form-label">Time To</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>09:45</option>
                                                        <option>10:45</option>
                                                        <option>11:30</option>
                                                        <option>12:15</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3 ms-2">
                                                    <a href="#" class="delete-time-table"><i
                                                            class="ti ti-trash"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="javascript:void(0);" class="btn btn-primary add-new-timetable"><i
                                            class="ti ti-square-rounded-plus-filled me-2"></i>Add New</a>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pills-wednesday" role="tabpanel"
                                aria-labelledby="pills-wednesday-tab">
                                <div class="add-timetable-row">
                                    <div class="row timetable-count">
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Subject</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>English</option>
                                                    <option>Spanish</option>
                                                    <option>Physics</option>
                                                    <option>Maths</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Teacher</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Hellana</option>
                                                    <option>Erickson</option>
                                                    <option>Teresa</option>
                                                    <option>Aaron</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Time From</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>09:00</option>
                                                    <option>09:45</option>
                                                    <option>10:45</option>
                                                    <option>11:30</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="d-flex align-items-end">
                                                <div class="mb-3 flex-fill">
                                                    <label class="form-label">Time To</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>09:45</option>
                                                        <option>10:45</option>
                                                        <option>11:30</option>
                                                        <option>12:15</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3 ms-2">
                                                    <a href="#" class="delete-time-table"><i
                                                            class="ti ti-trash"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="javascript:void(0);" class="btn btn-primary add-new-timetable"><i
                                            class="ti ti-square-rounded-plus-filled me-2"></i>Add New</a>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pills-thursday" role="tabpanel"
                                aria-labelledby="pills-thursday-tab">
                                <div class="add-timetable-row">
                                    <div class="row timetable-count">
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Subject</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>English</option>
                                                    <option>Spanish</option>
                                                    <option>Physics</option>
                                                    <option>Maths</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Teacher</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Hellana</option>
                                                    <option>Erickson</option>
                                                    <option>Teresa</option>
                                                    <option>Aaron</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Time From</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>09:00</option>
                                                    <option>09:45</option>
                                                    <option>10:45</option>
                                                    <option>11:30</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="d-flex align-items-end">
                                                <div class="mb-3 flex-fill">
                                                    <label class="form-label">Time To</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>09:45</option>
                                                        <option>10:45</option>
                                                        <option>11:30</option>
                                                        <option>12:15</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3 ms-2">
                                                    <a href="#" class="delete-time-table"><i
                                                            class="ti ti-trash"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="javascript:void(0);" class="btn btn-primary add-new-timetable"><i
                                            class="ti ti-square-rounded-plus-filled me-2"></i>Add New</a>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pills-friday" role="tabpanel"
                                aria-labelledby="pills-friday-tab">
                                <div class="add-timetable-row">
                                    <div class="row timetable-count">
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Subject</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>English</option>
                                                    <option>Spanish</option>
                                                    <option>Physics</option>
                                                    <option>Maths</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Teacher</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Hellana</option>
                                                    <option>Erickson</option>
                                                    <option>Teresa</option>
                                                    <option>Aaron</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="mb-3">
                                                <label class="form-label">Time From</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>09:00</option>
                                                    <option>09:45</option>
                                                    <option>10:45</option>
                                                    <option>11:30</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="d-flex align-items-end">
                                                <div class="mb-3 flex-fill">
                                                    <label class="form-label">Time To</label>
                                                    <select class="select">
                                                        <option>Select</option>
                                                        <option>09:45</option>
                                                        <option>10:45</option>
                                                        <option>11:30</option>
                                                        <option>12:15</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3 ms-2">
                                                    <a href="#" class="delete-time-table"><i
                                                            class="ti ti-trash"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="javascript:void(0);" class="btn btn-primary add-new-timetable"><i
                                            class="ti ti-square-rounded-plus-filled me-2"></i>Add New</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Time Table</button>
                </div>
            </form>
        </div>

    </div>
</div>
<!-- /Add Class Time Table -->

@endif
@if (Route::is(['teacher-leaves']))
   <!-- Apply Leave -->
	<div class="modal fade" id="apply_leave">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Apply Leave</h4>
					<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
						<i class="ti ti-x"></i>
					</button>
				</div>					
				<form action="{{url('student-leaves')}}">
					<div class="modal-body">		
						<div class="row">
							<div class="col-md-12">
								<div class="mb-3">
									<label class="form-label">Leave Date</label>
									<div class="date-pic">
										<input type="text" class="form-control datetimepicker" placeholder="15 May 2024">
										<span class="cal-icon"><i class="ti ti-calendar"></i></span>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">Leave Type</label>
									<select class="select">
										<option>Select</option>
										<option>Medical Leave</option>
										<option>Casual Leave</option>
										<option>Special Leave</option>
										<option>Maternity Leave</option>
									</select>
								</div>
								<div class="mb-3">
									<label class="form-label">Leave From date</label>
									<div class="date-pic">
										<input type="text" class="form-control datetimepicker" placeholder="15 May 2024">
										<span class="cal-icon"><i class="ti ti-calendar"></i></span>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">Leave to Date</label>
									<div class="date-pic">
										<input type="text" class="form-control datetimepicker" placeholder="15 May 2024">
										<span class="cal-icon"><i class="ti ti-calendar"></i></span>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">Leave Days</label>
									<div class="d-flex align-items-center check-radio-group">
										<label class="custom-radio">
											<input type="radio" name="radio" checked="">
											<span class="checkmark"></span>
											Full day
										</label>
										<label class="custom-radio">
											<input type="radio" name="radio">
											<span class="checkmark"></span>
											First Half
										</label>
										<label class="custom-radio">
											<input type="radio" name="radio">
											<span class="checkmark"></span>
											Second Half
										</label>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">No of Days</label>
									<input type="text" class="form-control">
								</div>
								<div class="mb-0">
									<label class="form-label">Reason</label>
									<input type="text" class="form-control">
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
						<button type="submit" class="btn btn-primary">Apply Leave</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<!-- /Apply Leave -->	

	<!-- Login Details -->
	<div class="modal fade" id="login_detail">
		<div class="modal-dialog modal-dialog-centered  modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Login Details</h4>
					<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
						<i class="ti ti-x"></i>
					</button>
				</div>
				<div class="modal-body">
					<div class="student-detail-info">
						<span class="student-img"><img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="Img"></span>
						<div class="name-info">
							<h6>Janet <span>III, A</span></h6>
						</div>
					</div>
					<div class="table-responsive custom-table no-datatable_length">
						<table class="table datanew">
							<thead class="thead-light">
								<tr>
									<th>User Type</th>
									<th>User Name</th>
									<th>Password </th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>Parent</td>
									<td>parent53</td>
									<td>parent@53</td>
								</tr>
								<tr>
									<td>Student</td>
									<td>student20</td>
									<td>stdt@53</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<div class="modal-footer">
					<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
				</div>
			</div>
		</div>
	</div>
	<!-- /Login Details -->
		
@endif

@if (Route::is(['classes']))
  <!-- Add Classes -->
  <div class="modal fade" id="add_class">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Class</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('classes')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Class Name</label>
                                <input type="text" class="form-control" >
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Section</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>A</option>
                                    <option>B</option>
                                    <option>C</option>
                                    <option>D</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">No of Students</label>
                                <input type="text" class="form-control" >
                            </div>
                            <div class="mb-3">
                                <label class="form-label">No of Subjects</label>
                                <input type="text" class="form-control">
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Class</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Classes -->

<!-- Edit Classes -->
<div class="modal fade" id="edit_class">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Class</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('classes')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Class Name</label>
                                <input type="text" class="form-control" placeholder="Enter Class Name"
                                    value="I">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Section</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option selected>A</option>
                                    <option>B</option>
                                    <option>C</option>
                                    <option>D</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">No of Students</label>
                                <input type="text" class="form-control" placeholder="Enter no of Students"
                                    value="30">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">No of Subjects</label>
                                <input type="text" class="form-control" placeholder="Enter no of Subjects"
                                    value="03">
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Edit Classes -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('classes')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->

<!-- View Classes -->
<div class="modal fade" id="view_class">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center modal-pop-head">
                    <h4 class="modal-title">Class Details</h4>
                    <span class="badge badge-soft-success ms-2"><i class="ti ti-circle-filled"></i>Active</span>
                </div>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('classes')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="class-detail-info">
                                <p>Class Name</p>
                                <span>III</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="class-detail-info">
                                <p>Section</p>
                                <span>A</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="class-detail-info">
                                <p>No of Subjects</p>
                                <span>05</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="class-detail-info">
                                <p>No of Students</p>
                                <span>25</span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /View Classes -->

@endif

@if (Route::is(['schedule-classes']))
   <!-- Add Schedule -->
		<div class="modal fade" id="add_Schedule">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Schedule</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('schedule-classes')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Type</label>
										<select class="select">
											<option>Select</option>
											<option>Class</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Start Time </label>
										<select class="select">
											<option>Select</option>
											<option>09:30 AM</option>
											<option>10:30 AM</option>
											<option>11:30 AM</option>
											<option>12:30 PM</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">End Time </label>
										<select class="select">
											<option>Select</option>
											<option>10:30 AM</option>
											<option>11:30 AM</option>
											<option>12:30 PM</option>
											<option>01:30 PM</option>
										</select>
									</div>
									<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user1" class="check">
											<label for="user1" class="checktoggle"> </label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Schedule</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Schedule -->

		<!-- Edit Schedule -->
		<div class="modal fade" id="edit_Schedule">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Schedule</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('schedule-classes')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Type</label>
										<select class="select">
											<option>Class</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Start Time </label>
										<select class="select">
											<option>Select</option>
											<option selected>09:30 AM</option>
											<option>10:30 AM</option>
											<option>11:30 AM</option>
											<option>12:30 PM</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">End Time </label>
										<select class="select">
											<option>Select</option>
											<option selected>10:30 AM</option>
											<option>11:30 AM</option>
											<option>12:30 PM</option>
											<option>01:30 PM</option>
										</select>
									</div>
									<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user2" class="check" checked="">
											<label for="user2" class="checktoggle"> </label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Schedule -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('schedule-classes')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3"
									data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->
@endif

@if (Route::is(['class-routine']))
   	<!-- Add Class Routine -->
		<div class="modal fade" id="add_class_routine">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Class Routine</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('class-routine')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Teacher</label>
										<select class="select">
											<option>Select</option>
											<option>Erickson</option>
											<option>Mori</option>
											<option>Joseph</option>
											<option>James</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Class</label>
										<select class="select">
											<option>Select</option>
											<option>I</option>
											<option>II</option>
											<option>III</option>
											<option>IV</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Section</label>
										<select class="select">
											<option>Select</option>
											<option>A</option>
											<option>B</option>
											<option>C</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Day</label>
										<select class="select">
											<option>Select</option>
											<option>Monday</option>
											<option>Tuesday</option>
											<option>Wedneshday</option>
											<option>Thursday</option>
											<option>Friday</option>
										</select>
									</div>
									<div class="row">
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Start Time</label>
												<div class="date-pic">
													<input type="text" class="form-control timepicker"
														placeholder="Choose">
													<span class="cal-icon"><i class="ti ti-clock"></i></span>
												</div>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">End Time</label>
												<div class="date-pic">
													<input type="text" class="form-control timepicker"
														placeholder="Choose">
													<span class="cal-icon"><i class="ti ti-clock"></i></span>
												</div>
											</div>
										</div>
									</div>
									<div class="mb-3">
										<label class="form-label">Class Room</label>
										<select class="select">
											<option>Select</option>
											<option>101</option>
											<option>102</option>
											<option>103</option>
											<option>104</option>
											<option>105</option>
										</select>
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="form-check form-switch">
											<input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Class Routine</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Class Routine -->

		<!-- Edit Class Routine -->
		<div class="modal fade" id="edit_class_routine">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Class Routine</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('class-routine')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Teacher</label>
										<select class="select">
											<option>Select</option>
											<option selected>Erickson</option>
											<option>Mori</option>
											<option>Joseph</option>
											<option>James</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Class</label>
										<select class="select">
											<option>Select</option>
											<option selected>I</option>
											<option>II</option>
											<option>III</option>
											<option>IV</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Section</label>
										<select class="select">
											<option>Select</option>
											<option selected>A</option>
											<option>B</option>
											<option>C</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Day</label>
										<select class="select">
											<option selected>Select</option>
											<option>Monday</option>
											<option>Tuesday</option>
											<option>Wedneshday</option>
											<option>Thursday</option>
											<option>Friday</option>
										</select>
									</div>
									<div class="row">
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Start Time</label>
												<div class="date-pic">
													<input type="text" class="form-control timepicker"
														placeholder="Choose">
													<span class="cal-icon"><i class="ti ti-clock"></i></span>
												</div>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">End Time</label>
												<div class="date-pic">
													<input type="text" class="form-control timepicker"
														placeholder="Choose">
													<span class="cal-icon"><i class="ti ti-clock"></i></span>
												</div>
											</div>
										</div>
									</div>
									<div class="mb-3">
										<label class="form-label">Class Room</label>
										<select class="select">
											<option>Select</option>
											<option selected>101</option>
											<option>102</option>
											<option>103</option>
											<option>104</option>
											<option>105</option>
										</select>
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="form-check form-switch">
											<input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Class Routine -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('class-routine')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

@endif
@if (Route::is(['class-report']))
   	<!-- Add Expenses Category -->
       <div class="modal fade" id="view_class_report">
        <div class="modal-dialog modal-dialog-centered  modal-xl">
            <div class="modal-content">
                <div class="modal-wrapper">
                    <div class="modal-body">

                        <!-- Student List -->
                        <div class="table-responsive">
                            <table class="table datatable">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="no-sort">
                                            <label class="checkboxs"><input type="checkbox"
                                                    id="select-all2"><span class="checkmarks"></span></label>
                                        </th>
                                        <th>Admission No</th>
                                        <th>Roll No</th>
                                        <th>Name</th>
                                        <th>Class </th>
                                        <th>Section</th>
                                        <th>Gender</th>
                                        <th>Parent Name</th>
                                        <th>DOB</th>
                                        <th>Status</th>
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
                                        <td><a href="#" class="link-primary">AD9892434</a></td>
                                        <td>35013</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-01.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="{{url('student-details')}}">Janet</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>III</td>
                                        <td>A</td>
                                        <td>Female</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/parents/parent-01.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="javascript:void(0);">Thomas</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>10 Jan 2015</td>
                                        <td>
                                            <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                    class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label class="checkboxs">
                                                <input type="checkbox">
                                                <span class="checkmarks"></span>
                                            </label>
                                        </td>
                                        <td><a href="#" class="link-primary">AD9892433</a></td>
                                        <td>35013</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-02.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="{{url('student-details')}}">Joann</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>IV</td>
                                        <td>B</td>
                                        <td>Male</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/parents/parent-02.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="javascript:void(0);">Marquita</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>19 Aug 2014</td>
                                        <td>
                                            <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                    class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label class="checkboxs">
                                                <input type="checkbox">
                                                <span class="checkmarks"></span>
                                            </label>
                                        </td>
                                        <td><a href="#" class="link-primary">AD9892432</a></td>
                                        <td>35011</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-03.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="{{url('student-details')}}">Kathleen</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>II</td>
                                        <td>A</td>
                                        <td>Female</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/parents/parent-03.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a href="javascript:void(0);">
                                                            Johnson</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>05 Dec 2017</td>
                                        <td>
                                            <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                    class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label class="checkboxs">
                                                <input type="checkbox">
                                                <span class="checkmarks"></span>
                                            </label>
                                        </td>
                                        <td><a href="#" class="link-primary">AD9892431</a></td>
                                        <td>35010</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-04.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="{{url('student-details')}}">Gifford</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>I</td>
                                        <td>B</td>
                                        <td>Male</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/parents/parent-04.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="javascript:void(0);">Claudia</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>22 Mar 2018</td>
                                        <td>
                                            <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                    class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label class="checkboxs">
                                                <input type="checkbox">
                                                <span class="checkmarks"></span>
                                            </label>
                                        </td>
                                        <td><a href="#" class="link-primary">AD9892430</a></td>
                                        <td>35009</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-05.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="{{url('student-details')}}">Lisa</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>II</td>
                                        <td>B</td>
                                        <td>Female</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/parents/parent-05.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="javascript:void(0);">Arthur </a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>13 May 2017</td>
                                        <td>
                                            <span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                                    class="ti ti-circle-filled fs-5 me-1"></i>Inactive</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label class="checkboxs">
                                                <input type="checkbox">
                                                <span class="checkmarks"></span>
                                            </label>
                                        </td>
                                        <td><a href="#" class="link-primary">AD9892429</a></td>
                                        <td>35008</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-06.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="{{url('student-details')}}">Ralph</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>III</td>
                                        <td>B</td>
                                        <td>Male</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/parents/parent-06.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="javascript:void(0);">Colleen</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>20 Jun 2015</td>
                                        <td>
                                            <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                    class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label class="checkboxs">
                                                <input type="checkbox">
                                                <span class="checkmarks"></span>
                                            </label>
                                        </td>
                                        <td><a href="#" class="link-primary">AD9892428</a></td>
                                        <td>35007</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-07.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="{{url('student-details')}}">Julie</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>V</td>
                                        <td>A</td>
                                        <td>Female</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/parents/parent-07.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="javascript:void(0);">Robert </a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>18 Sep 2013</td>
                                        <td>
                                            <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                    class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label class="checkboxs">
                                                <input type="checkbox">
                                                <span class="checkmarks"></span>
                                            </label>
                                        </td>
                                        <td><a href="#" class="link-primary">AD9892427</a></td>
                                        <td>35006</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-06.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="{{url('student-details')}}">Ryan</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>VI</td>
                                        <td>A</td>
                                        <td>Male</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/parents/parent-08.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="javascript:void(0);">Jessie </a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>26 Nov 2012</td>
                                        <td>
                                            <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                    class="ti ti-circle-filled fs-5 me-2"></i>Active</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label class="checkboxs">
                                                <input type="checkbox">
                                                <span class="checkmarks"></span>
                                            </label>
                                        </td>
                                        <td><a href="#" class="link-primary">AD9892426</a></td>
                                        <td>35005</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-09.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="{{url('student-details')}}">Susan</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>VIII</td>
                                        <td>B</td>
                                        <td>Female</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/parents/parent-09.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="javascript:void(0);">Michael </a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>26 May 2010</td>
                                        <td>
                                            <span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                                    class="ti ti-circle-filled fs-5 me-1"></i>Inactive</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label class="checkboxs">
                                                <input type="checkbox">
                                                <span class="checkmarks"></span>
                                            </label>
                                        </td>
                                        <td><a href="#" class="link-primary">AD9892425</a></td>
                                        <td>35004</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-10.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="{{url('student-details')}}">Richard</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>VII</td>
                                        <td>B</td>
                                        <td>Male</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/parents/parent-10.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="mb-0" class="text-dark"><a
                                                            href="javascript:void(0);">Mary</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>06 Oct 2011</td>
                                        <td>
                                            <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                    class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- /Student List -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Add Expenses Category -->
@endif

@if (Route::is(['class-room']))
   <!-- Add Class Room -->
		<div class="modal fade" id="add_class_room">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Class Room</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('class-room')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Room No</label>
										<input type="text" class="form-control" placeholder="Enter Room no">
									</div>
									<div class="mb-3">
										<label class="form-label">Capacity</label>
										<input type="text" class="form-control" placeholder="Enter Capacity">
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="form-check form-switch">
											<input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Class Room</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Class Room -->

		<!-- Edit Class Room -->
		<div class="modal fade" id="edit_class_room">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Class Room</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('class-room')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Room No</label>
										<input type="text" class="form-control" placeholder="Enter Room no"
											value="110">
									</div>
									<div class="mb-3">
										<label class="form-label">Capacity</label>
										<input type="text" class="form-control" placeholder="Enter Capacity"
											value="50">
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="form-check form-switch">
											<input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
										</div>
									</div>
								</div>
							</div>
						</div>						
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Class Room -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('class-room')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

@endif

@if (Route::is(['class-section']))
   <!-- Add Class Section -->
		<div class="modal fade" id="add_class_section">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Section</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('class-section')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Section</label>
										<input type="text" class="form-control" placeholder="Enter Section">
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="form-check form-switch">
											<input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Section</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Class Section -->

		<!-- Edit Class Section -->
		<div class="modal fade" id="edit_class_section">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Section</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('class-section')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Section</label>
										<input type="text" class="form-control" placeholder="Enter Section"
											value="A">
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="form-check form-switch">
											<input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Class Section -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('class-section')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

@endif

@if (Route::is(['class-subject']))
  <!-- Add Subject -->
  <div class="modal fade" id="add_subject">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Subject</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('class-subject')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" placeholder="Enter Name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Code</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>101</option>
                                    <option>102</option>
                                    <option>103</option>
                                    <option>104</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Theory</option>
                                    <option>Practical</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Subject -->

<!-- Edit Subject -->
<div class="modal fade" id="edit_subject">
    <div class="modal-dialog modal-dialog-centere">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Subject</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('class-subject')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" placeholder="Enter Name"
                                    value="English">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Code</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option selected>101</option>
                                    <option>102</option>
                                    <option>103</option>
                                    <option>104</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option selected>Theory</option>
                                    <option>Practical</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Edit Subject -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('class-subject')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->
@endif

@if (Route::is(['class-home-work']))
  <!-- Add Home Work -->
  <div class="modal fade" id="add_home_work">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Home Work</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('class-home-work')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Class</label>
                                <input type="text" class="form-control">
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Section</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>A</option>
                                            <option>B</option>
                                            <option>C</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Subject</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>English</option>
                                            <option>Physics</option>
                                            <option>Chemsitry</option>
                                            <option>Biology</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Homework Date</label>
                                        <input type="text" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Submission Date</label>
                                        <input type="text" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Attachments</label>
                                <input type="text" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Attachments</label>
                                <textarea class="form-control" rows="4"></textarea>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="switch-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Homework</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Home Work -->

<!-- Edit Home Work -->
<div class="modal fade" id="edit_home_work">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Home Work</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('class-home-work')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Class</label>
                                <input type="text" class="form-control" placeholder="Enter Class" value="I">
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Section</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option selected>A</option>
                                            <option>B</option>
                                            <option>C</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Subject</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option selected>English</option>
                                            <option>Physics</option>
                                            <option>Chemsitry</option>
                                            <option>Biology</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Homework Date</label>
                                        <input type="text" class="form-control" placeholder="10 May 2024">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Submission Date</label>
                                        <input type="text" class="form-control" placeholder="12 May 2024">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Attachments</label>
                                <input type="text" class="form-control" placeholder="Placeholders">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Attachments</label>
                                <textarea class="form-control" placeholder="Add Comment" rows="4"></textarea>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="switch-sm2">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Edit Home Work -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('class-home-work')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->
@endif

@if (Route::is(['exam']))
    <!-- Add Exam -->
		<div class="modal fade" id="add_exam">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Exam</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('exam')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Exam Name</label>
										<input type="text" class="form-control" placeholder="Enter Exam Name">
									</div>
									<div class="mb-3">
										<label class="form-label">Exam Date</label>
										<div class="date-pic">
											<input type="text" class="form-control datetimepicker"
												placeholder="Select">
											<span class="cal-icon"><i class="ti ti-calendar"></i></span>
										</div>
									</div>
									<div class="mb-3">
										<label class="form-label">Start Time</label>
										<select class="select">
											<option>Select</option>
											<option>09:30 AM</option>
											<option>10:30 AM</option>
											<option>11:00 AM</option>
											<option>12:30 PM</option>
										</select>
									</div>
									<div class="mb-0">
										<label class="form-label">End Time</label>
										<select class="select">
											<option>Select</option>
											<option>10:45 AM</option>
											<option>11:00 AM</option>
											<option>11:30 AM</option>
											<option>12:00 PM</option>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Exam</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Add Exam -->

		<!-- Edit Exam -->
		<div class="modal fade" id="edit_exam">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Exam</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('exam')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Exam Name</label>
										<input type="text" class="form-control" placeholder="Enter Exam Name"
											value="Week Test">
									</div>
									<div class="mb-3">
										<label class="form-label">Exam Date</label>
										<div class="date-pic">
											<input type="text" class="form-control datetimepicker"
												placeholder="Select">
											<span class="cal-icon"><i class="ti ti-calendar"></i></span>
										</div>
									</div>
									<div class="mb-3">
										<label class="form-label">Start Time</label>
										<select class="select">
											<option>Select</option>
											<option selected>09:30 AM</option>
											<option>10:30 AM</option>
											<option>11:00 AM</option>
											<option>12:30 PM</option>
										</select>
									</div>
									<div class="mb-0">
										<label class="form-label">End Time</label>
										<select class="select">
											<option>Select</option>
											<option selected>10:45 AM</option>
											<option>11:00 AM</option>
											<option>11:30 AM</option>
											<option>12:00 PM</option>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Edit Exam -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('exam')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

@endif

@if (Route::is(['exam-schedule']))
<!-- Add Exam Schedule -->
<div class="modal fade" id="add_exam_schedule">
    <div class="modal-dialog modal-dialog-centered  modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Exam Schedule</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('exam-schedule')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Class</label>
                                        <input type="text" class="form-control" placeholder="Enter Class">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Section</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>A</option>
                                            <option>B</option>
                                            <option>C</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Exam Name</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>Week text</option>
                                            <option>Mothly Test</option>
                                            <option>Chapter Wise Test</option>
                                            <option>Unit Test</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Start Time</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>09:30 AM</option>
                                            <option>10:30 AM</option>
                                            <option>11:00 AM</option>
                                            <option>12:30 PM</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">End Time</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>10:45 AM</option>
                                            <option>11:00 AM</option>
                                            <option>11:30 AM</option>
                                            <option>12:00 PM</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Duration(min)</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>3 hrs</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="exam-schedule-add">	
                        <div class="exam-schedule-row d-flex align-items-center flex-wrap column-gap-3">
                            <div class="shedule-info flex-fill">
                                <div class="mb-3">
                                    <label class="form-label">Exam Date</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>13 May 2024 </option>
                                        <option>14 May 2024  </option>
                                        <option>15 May 2024  </option>
                                    </select>
                                </div>
                            </div>
                            <div class="shedule-info flex-fill">
                                <div class="mb-3">
                                    <label class="form-label">Subject</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>English</option>
                                        <option>Spanish</option>
                                        <option>Physics</option>
                                    </select>
                                </div>
                            </div>
                            <div class="shedule-info flex-fill">
                                <div class="mb-3">
                                    <label class="form-label">Room No</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>101</option>
                                        <option>103</option>
                                        <option>104</option>
                                    </select>
                                </div>
                            </div>
                            <div class="shedule-info flex-fill">
                                <div class="mb-3">
                                    <label class="form-label">Max Marks</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>100</option>
                                    </select>
                                </div>
                            </div>
                            <div class="shedule-info flex-fill">
                                <div class="d-flex align-items-end">
                                    <div class="mb-3 flex-fill">
                                        <label class="form-label">Min Marks</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>35</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 ms-2">
                                        <a href="#" class="delete-schedule-table"><i class="ti ti-trash"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="javascript:void(0);" class="btn btn-primary add-new-schedule"><i class="ti ti-square-rounded-plus-filled me-2"></i>Add New</a>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Exam Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Exam Schedule -->

<!-- Edit Exam Schedule -->
<div class="modal fade" id="edit_exam_schedule">
    <div class="modal-dialog modal-dialog-centered  modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Exam Schedule</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('exam-schedule')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Class</label>
                                        <input type="text" class="form-control" placeholder="Enter Class" value="I">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Section</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option selected>A</option>
                                            <option>B</option>
                                            <option>C</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Exam Name</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option selected>Week text</option>
                                            <option>Mothly Test</option>
                                            <option>Chapter Wise Test</option>
                                            <option>Unit Test</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Start Time</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>09:30 AM</option>
                                            <option>10:30 AM</option>
                                            <option>11:00 AM</option>
                                            <option>12:30 PM</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">End Time</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>10:45 AM</option>
                                            <option>11:00 AM</option>
                                            <option>11:30 AM</option>
                                            <option>12:00 PM</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Duration(min)</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option selected>3 hrs</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="exam-schedule-add">	
                        <div class="exam-schedule-row d-flex align-items-center flex-wrap column-gap-3">
                            <div class="shedule-info flex-fill">
                                <div class="mb-3">
                                    <label class="form-label">Exam Date</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>13 May 2024 </option>
                                        <option>14 May 2024  </option>
                                        <option>15 May 2024  </option>
                                    </select>
                                </div>
                            </div>
                            <div class="shedule-info flex-fill">
                                <div class="mb-3">
                                    <label class="form-label">Subject</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>English</option>
                                        <option>Spanish</option>
                                        <option>Physics</option>
                                    </select>
                                </div>
                            </div>
                            <div class="shedule-info flex-fill">
                                <div class="mb-3">
                                    <label class="form-label">Room No</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>101</option>
                                        <option>103</option>
                                        <option>104</option>
                                    </select>
                                </div>
                            </div>
                            <div class="shedule-info flex-fill">
                                <div class="mb-3">
                                    <label class="form-label">Max Marks</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>100</option>
                                    </select>
                                </div>
                            </div>
                            <div class="shedule-info flex-fill">
                                <div class="d-flex align-items-end">
                                    <div class="mb-3 flex-fill">
                                        <label class="form-label">Min Marks</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>35</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Exam Schedule -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('exam-schedule')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->

   @endif

@if (Route::is(['grade']))
   <!-- Add Grade -->
		<div class="modal fade" id="add_grade">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Grade</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('grade')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Grade</label>
										<input type="text" class="form-control" placeholder="Enter Grade">
									</div>
									<div class="mb-3">
										<label class="form-label">Marks From(%)</label>
										<select class="select">
											<option>Select</option>
											<option>90</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Marks Upto(%)</label>
										<select class="select">
											<option>Select</option>
											<option>100</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Grade Points</label>
										<select class="select">
											<option>Select</option>
											<option>0</option>
											<option>1</option>
											<option>2</option>
											<option>3</option>
											<option>4</option>
											<option>5</option>
											<option>6</option>
											<option>7</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Status</label>
										<select class="select">
											<option>Select</option>
											<option>Active</option>
											<option>Inactive</option>
										</select>
									</div>
									<div class="mb-0">
										<label class="form-label">Description</label>
										<textarea class="form-control" rows="4"
											placeholder="Add Comment"></textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Grade</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Add Grade -->

		<!-- Edit Grade -->
		<div class="modal fade" id="edit_grade">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Grade</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('grade')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Grade</label>
										<input type="text" class="form-control" placeholder="Enter Grade" value="O">
									</div>
									<div class="mb-3">
										<label class="form-label">Marks From(%)</label>
										<select class="select">
											<option>Select</option>
											<option selected>90</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Marks Upto(%)</label>
										<select class="select">
											<option>Select</option>
											<option selected>100</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Grade Points</label>
										<select class="select">
											<option>Select</option>
											<option selected>0</option>
											<option>1</option>
											<option>2</option>
											<option>3</option>
											<option>4</option>
											<option>5</option>
											<option>6</option>
											<option>7</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Status</label>
										<select class="select">
											<option>Select</option>
											<option selected>Active</option>
											<option>Inactive</option>
										</select>
									</div>
									<div class="mb-0">
										<label class="form-label">Description</label>
										<textarea class="form-control" rows="4"
											placeholder="Add Comment"></textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Edit Grade -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('grade')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

@endif

@if (Route::is(['exam-attendance']))
    <!-- Edit Grade -->
    <div class="modal fade" id="edit_grade">
        <div class="modal-dialog modal-dialog-centered custom-modal">
            <div class="modal-content">
                <div class="modal-wrapper">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Grade</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form action="grade">
                        <div class="modal-card">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="col-form-label">Grade</label>
                                        <input type="text" class="form-control" placeholder="Enter Grade"
                                            value="O">
                                    </div>
                                    <div class="mb-4">
                                        <label class="col-form-label">Marks From(%)</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option selected>90</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="col-form-label">Marks Upto(%)</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option selected>100</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="col-form-label">Grade Points</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option selected>0</option>
                                            <option>1</option>
                                            <option>2</option>
                                            <option>3</option>
                                            <option>4</option>
                                            <option>5</option>
                                            <option>6</option>
                                            <option>7</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="col-form-label">Status</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option selected>Active</option>
                                            <option>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="col-form-label">Description</label>
                                        <textarea class="form-control" placeholder="Add Comment"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-btn text-end">
                            <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Grade -->
@endif

@if (Route::is('connected-apps'))
   <!-- Connect App -->
		<div class="modal fade" id="connect_app">
			<div class="modal-dialog modal-dialog-centered  connected-app-modal">
				<div class="modal-content">
					<div class="modal-body">
						<div class="d-flex align-items-center justify-content-center">
							<span class="modal-logo-small me-3"><img src="{{URL::asset('build/img/icons/modal-small-logo.svg')}}" alt="Img"></span>
							<div class="connected-design">
								<span></span>
								<span></span>
							</div>
							<span><img src="{{URL::asset('build/img/icons/connected-app-01.svg')}}" alt="Img"></span>
						</div>
						<div class="border-bottom pb-3 mb-3">
							<h5 class="mb-2">Connect with Slack</h5>
							<p>Confirm requirements to connect with Slack</p>
						</div>
						<div>
							<h5 class="mb-2">Slack requires :</h5>
							<ul>
								<li class="mb-2 d-flex align-items-center">
									<span class="avatar avatar-xs rounded-circle bg-success border-0 me-2"><i class="ti ti-check fs-14"></i></span>
									New Slack Workspace for communication
								</li>
								<li class="mb-2 d-flex align-items-center">
									<span class="avatar avatar-xs rounded-circle bg-success border-0 me-2"><i class="ti ti-check fs-14"></i></span>
									Slack API to interact with Slack
								</li>
								<li class="d-flex align-items-center">
									<span class="avatar avatar-xs rounded-circle bg-success border-0 me-2"><i class="ti ti-check fs-14"></i></span>
									Implement OAuth 2.0 for authorization
								</li>
							</ul>
						</div>
					</div>
					<div class="modal-footer">
						<form action="{{url('connected-apps')}}" class="w-100">
							<div class="d-flex align-items-center mb-3">
								<input type="text" class="form-control flex-fill">
								<a class="border rounded d-flex align-items-center justify-content-center p-2 ms-3"><i class="ti ti-copy"></i></a>
							</div>
							<div class="d-flex align-items-center">
								<button type="button" class="btn btn-light me-3 flex-fill">Cancel</button>
								<button type="submit" class="btn btn-primary flex-fill">Connect</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<!-- /Connect App -->

		<!-- Connect App -->
		<div class="modal fade" id="connect_app_two">
			<div class="modal-dialog modal-dialog-centered  connected-app-modal">
				<div class="modal-content">
					<div class="modal-body">
						<div class="d-flex align-items-center justify-content-center">
							<span class="modal-logo-small me-3"><img src="{{URL::asset('build/img/icons/modal-small-logo.svg')}}" alt="Img"></span>
							<div class="connected-design">
								<span></span>
								<span></span>
							</div>
							<span><img src="{{URL::asset('build/img/icons/connected-app-02.svg')}}" alt="Img"></span>
						</div>
						<div class="border-bottom pb-3 mb-3">
							<h5 class="mb-2">Connect with Google Calendar</h5>
							<p>Confirm requirements to connect with Google Calendar</p>
						</div>
						<div>
							<h5 class="mb-2">Slack requires :</h5>
							<ul>
								<li class="mb-2 d-flex align-items-center">
									<span class="avatar avatar-xs rounded-circle bg-success border-0 me-2"><i class="ti ti-check fs-16"></i></span>
									New Slack Workspace for communication
								</li>
								<li class="mb-2 d-flex align-items-center">
									<span class="avatar avatar-xs rounded-circle bg-success border-0 me-2"><i class="ti ti-check fs-14"></i></span>
									Slack API to interact with Slack
								</li>
								<li class="d-flex align-items-center">
									<span class="avatar avatar-xs rounded-circle bg-success border-0 me-2"><i class="ti ti-check fs-14"></i></span>
									Implement OAuth 2.0 for authorization
								</li>
							</ul>
						</div>
					</div>
					<div class="modal-footer">
						<form action="{{url('connected-apps')}}" class="w-100">
							<div class="d-flex align-items-center mb-3">
								<input type="text" class="form-control flex-fill">
								<a class="border rounded d-flex align-items-center justify-content-center p-2 ms-3"><i class="ti ti-copy"></i></a>
							</div>
							<div class="d-flex align-items-center">
								<button type="button" class="btn btn-light me-3 flex-fill">Cancel</button>
								<button type="submit" class="btn btn-primary flex-fill">Connect</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<!-- /Connect App -->

		<!-- Connect App -->
		<div class="modal fade" id="connect_app_three">
			<div class="modal-dialog modal-dialog-centered  connected-app-modal">
				<div class="modal-content">
					<div class="modal-body">
						<div class="d-flex align-items-center justify-content-center">
							<span class="modal-logo-small me-3"><img src="{{URL::asset('build/img/icons/modal-small-logo.svg')}}" alt="Img"></span>
							<div class="connected-design">
								<span></span>
								<span></span>
							</div>
							<span><img src="{{URL::asset('build/img/icons/connected-app-03.svg')}}" alt="Img"></span>
						</div>
						<div class="border-bottom pb-3 mb-3">
							<h5 class="mb-2">Connect with Email</h5>
							<p>Confirm requirements to connect with email</p>
						</div>
						<div>
							<h5 class="mb-2">Slack requires :</h5>
							<ul>
								<li class="mb-2 d-flex align-items-center">
									<span class="avatar avatar-xs rounded-circle bg-success border-0 me-2"><i class="ti ti-check fs-16"></i></span>
									New Slack Workspace for communication
								</li>
								<li class="mb-2 d-flex align-items-center">
									<span class="avatar avatar-xs rounded-circle bg-success border-0 me-2"><i class="ti ti-check fs-14"></i></span>
									Slack API to interact with Slack
								</li>
								<li class="d-flex align-items-center">
									<span class="avatar avatar-xs rounded-circle bg-success border-0 me-2"><i class="ti ti-check fs-14"></i></span>
									Implement OAuth 2.0 for authorization
								</li>
							</ul>
						</div>
					</div>
					<div class="modal-footer">
						<form action="{{url('connected-apps')}}" class="w-100">
							<div class="d-flex align-items-center mb-3">
								<input type="text" class="form-control flex-fill">
								<a class="border rounded d-flex align-items-center justify-content-center p-2 ms-3"><i class="ti ti-copy"></i></a>
							</div>
							<div class="d-flex align-items-center">
								<button type="button" class="btn btn-light me-3 flex-fill">Cancel</button>
								<button type="submit" class="btn btn-primary flex-fill">Connect</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<!-- /Connect App -->

		<!-- Connect App -->
		<div class="modal fade" id="connect_app_four">
			<div class="modal-dialog modal-dialog-centered  connected-app-modal">
				<div class="modal-content">
					<div class="modal-body">
						<div class="d-flex align-items-center justify-content-center">
							<span class="modal-logo-small me-3"><img src="{{URL::asset('build/img/icons/modal-small-logo.svg')}}" alt="Img"></span>
							<div class="connected-design">
								<span></span>
								<span></span>
							</div>
							<span><img src="{{URL::asset('build/img/icons/connected-app-04.svg')}}" alt="Img"></span>
						</div>
						<div class="border-bottom pb-3 mb-3">
							<h5 class="mb-2">Connect with  Github</h5>
							<p>Confirm requirements to connect with Github</p>
						</div>
						<div>
							<h5 class="mb-2">Slack requires :</h5>
							<ul>
								<li class="mb-2 d-flex align-items-center">
									<span class="avatar avatar-xs rounded-circle bg-success border-0 me-2"><i class="ti ti-check fs-16"></i></span>
									New Slack Workspace for communication
								</li>
								<li class="mb-2 d-flex align-items-center">
									<span class="avatar avatar-xs rounded-circle bg-success border-0 me-2"><i class="ti ti-check fs-14"></i></span>
									Slack API to interact with Slack
								</li>
								<li class="d-flex align-items-center">
									<span class="avatar avatar-xs rounded-circle bg-success border-0 me-2"><i class="ti ti-check fs-14"></i></span>
									Implement OAuth 2.0 for authorization
								</li>
							</ul>
						</div>
					</div>
					<div class="modal-footer">
						<form action="{{url('connected-apps')}}" class="w-100">
							<div class="d-flex align-items-center mb-3">
								<input type="text" class="form-control flex-fill">
								<a class="border rounded d-flex align-items-center justify-content-center p-2 ms-3"><i class="ti ti-copy"></i></a>
							</div>
							<div class="d-flex align-items-center">
								<button type="button" class="btn btn-light me-3 flex-fill">Cancel</button>
								<button type="submit" class="btn btn-primary flex-fill">Connect</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<!-- /Connect App -->

@endif

@if (Route::is(['language']))
 	<!-- Add Language -->
     <div class="modal fade" id="add_language">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Language</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('language')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Language Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Language Name">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Code</label>
                                    <input type="text" class="form-control" placeholder="Enter Code">
                                </div>
                                <div
                                    class="modal-satus-toggle d-flex align-items-center justify-content-between mb-3">
                                    <div class="status-title">
                                        <h5>RTL</h5>
                                        <p>Change the Status by toggle </p>
                                    </div>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user5" class="check">
                                        <label for="user5" class="checktoggle"> </label>
                                    </div>
                                </div>
                                <div
                                    class="modal-satus-toggle d-flex align-items-center justify-content-between">
                                    <div class="status-title">
                                        <h5>Status</h5>
                                        <p>Change the Status by toggle </p>
                                    </div>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user6" class="check">
                                        <label for="user6" class="checktoggle"> </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Language</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Add Language -->

    <!-- Edit Language -->
    <div class="modal fade" id="edit_language">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Language</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('language')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Language Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Language Name"
                                        value="English">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Code</label>
                                    <input type="text" class="form-control" placeholder="Enter Code" value="en">
                                </div>
                                <div
                                    class="modal-satus-toggle d-flex align-items-center justify-content-between mb-3">
                                    <div class="status-title">
                                        <h5>RTL</h5>
                                        <p>Change the Status by toggle </p>
                                    </div>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user7" class="check" checked>
                                        <label for="user7" class="checktoggle"> </label>
                                    </div>
                                </div>
                                <div
                                    class="modal-satus-toggle d-flex align-items-center justify-content-between">
                                    <div class="status-title">
                                        <h5>Status</h5>
                                        <p>Change the Status by toggle </p>
                                    </div>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user8" class="check" checked>
                                        <label for="user8" class="checktoggle"> </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Language -->

    <!-- Language Setup -->
    <div class="modal fade" id="language_setup">
        <div class="modal-dialog modal-dialog-centered  modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Language Setup</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>					
                <div class="modal-body">		
                    <!-- Table Filter -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <div class="d-flex align-items-center">
                            <h5 class="mb-3 me-2">Search Criteria</h5>
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
                                                        <label class="form-label">Select Language</label>
                                                        <select class="select">
                                                            <option>English</option>
                                                            <option>Arabic</option>
                                                            <option>Chinese</option>
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
                    <!-- /Table Filter -->

                    <div class="table-responsive">
                        <div class="custom-table language-setup-table">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Default Phrases</th>
                                        <th>Change Phrases</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Description</td>
                                        <td>
                                            <input type="text" class="form-control" value="Text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Description</td>
                                        <td>
                                            <input type="text" class="form-control" value="Text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Description</td>
                                        <td>
                                            <input type="text" class="form-control" value="Text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Description</td>
                                        <td>
                                            <input type="text" class="form-control" value="Text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Description</td>
                                        <td>
                                            <input type="text" class="form-control" value="Text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Description</td>
                                        <td>
                                            <input type="text" class="form-control" value="Text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Description</td>
                                        <td>
                                            <input type="text" class="form-control" value="Text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Description</td>
                                        <td>
                                            <input type="text" class="form-control" value="Text">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Description</td>
                                        <td>
                                            <input type="text" class="form-control" value="Text">
                                        </td>
                                    </tr><tr>
                                        <td>Description</td>
                                        <td>
                                            <input type="text" class="form-control" value="Text">
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    <!-- /Language Setup -->

    <!-- Import File -->
    <div class="modal fade" id="import_file">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Import Files</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('language')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Module Name</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Students</option>
                                        <option>Parents</option>
                                        <option>Teachers</option>
                                    </select>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Language</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>English</option>
                                        <option>Arabic</option>
                                        <option>Chinese</option>
                                        <option>Hindi</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Import File -->

    <!-- Delete Modal -->
    <div class="modal fade" id="delete-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{url('language')}}">
                    <div class="modal-body text-center">
                        <span class="delete-icon">
                            <i class="ti ti-trash-x"></i>
                        </span>
                        <h4>Confirm Deletion</h4>
                        <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                        <div class="d-flex justify-content-center">
                            <a href="javascript:void(0);" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                            <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Delete Modal -->
@endif
@if(Route::is(['staffs']))
	<!-- Delete Modal -->
    <div class="modal fade" id="delete-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{url('staffs')}}">
                    <div class="modal-body text-center">
                        <span class="delete-icon">
                            <i class="ti ti-trash-x"></i>
                        </span>
                        <h4>Confirm Deletion</h4>
                        <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                        <div class="d-flex justify-content-center">
                            <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                            <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Delete Modal -->	
@endif
@if(Route::is(['custom-fields']))
   <!-- Add Custom Field -->
		<div class="modal fade" id="add_custom_field">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Custom Field</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>					
					<form action="{{url('custom-fields')}}">
						<div class="modal-body">		
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Choose Fields For</label>
										<select class="select">
											<option>Select</option>
											<option>Expense</option>
											<option>Inventory</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Label</label>
										<input type="text" class="form-control" placeholder="Enter Label">
									</div>
									<div class="mb-3">
										<label class="form-label">Type</label>
										<input type="text" class="form-control" placeholder="Enter Type">
									</div>
									<div class="mb-3">
										<label class="form-label">Default Value</label>
										<input type="text" class="form-control" placeholder="Enter Value">
									</div>
									<div class="mb-3">
										<label class="form-label">Required</label>
										<input type="text" class="form-control">
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="form-check form-switch">
											<input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Field</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Custom Field -->

		<!-- Edit Custom Field -->
		<div class="modal fade" id="edit_custom_field">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Custom Field</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>					
					<form action="{{url('custom-fields')}}">
						<div class="modal-body">		
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Choose Fields For</label>
										<select class="select">
											<option>Select</option>
											<option selected>Expense</option>
											<option>Inventory</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Label</label>
										<input type="text" class="form-control" placeholder="Enter Label" value="Text">
									</div>
									<div class="mb-3">
										<label class="form-label">Type</label>
										<input type="text" class="form-control" placeholder="Enter Type" value="Text">
									</div>
									<div class="mb-3">
										<label class="form-label">Default Value</label>
										<input type="text" class="form-control" placeholder="Enter Value" value="Text">
									</div>
									<div class="mb-3">
										<label class="form-label">Required</label>
										<input type="text" class="form-control" value="Text">
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="form-check form-switch">
											<input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Custom Field -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('custom-fields')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

@endif

@if(Route::is(['email-settings']))
   <!-- Connect Php -->
		<div class="modal fade" id="connect_php">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">PHP Mailer</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('email-settings')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">From Email Address</label>
										<input type="email" class="form-control" placeholder="Enter Email">
									</div>
									<div class="mb-3">
										<label class="form-label">Email Password</label>
										<input type="password" class="form-control" placeholder="Enter Password">
									</div>
									<div class="mb-3">
										<label class="form-label">From Email Name</label>
										<input type="password" class="form-control" placeholder="Enter Email Name">
									</div>
									<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user4" class="check" checked="">
											<label for="user4" class="checktoggle"> </label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Connect Php -->

		<!-- Connect Smtp -->
		<div class="modal fade" id="connect_smtp">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">SMTP</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('email-settings')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">From Email Address</label>
										<input type="email" class="form-control" placeholder="Enter Email">
									</div>
									<div class="mb-3">
										<label class="form-label">Email Password</label>
										<input type="password" class="form-control" placeholder="Enter Password">
									</div>
									<div class="mb-3">
										<label class="form-label">Email Host</label>
										<input type="password" class="form-control" placeholder="Enter Email Host">
									</div>
									<div class="mb-3">
										<label class="form-label">Port</label>
										<input type="password" class="form-control" placeholder="Enter Port">
									</div>
									<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user5" class="check" checked="">
											<label for="user5" class="checktoggle"> </label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Connect Smtp -->

		<!-- Connect Test Mail -->
		<div class="modal fade" id="connect_test_mail">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Google</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('email-settings')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-0">
										<label class="form-label">From Email Address</label>
										<input type="email" class="form-control" placeholder="Enter Email">
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Submit</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Connect Test Mail -->

@endif

@if (Route::is(['email-templates']))
   <!-- Add Email Template -->
		<div class="modal fade" id="add_email_template">
			<div class="modal-dialog modal-dialog-centered  modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Email Template</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('email-templates')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Title</label>
										<input type="text" class="form-control" placeholder="Enter Title">
									</div>
									<div class="mb-3">
										<label class="form-label">Subject</label>
										<input type="text" class="form-control" placeholder="Enter Subject">
									</div>
									<div class="mb-3">
										<label class="form-label">Template Content</label>
										<div class="input-blocks summer-description-box notes-summernote">
											<label class="form-label">Descriptions</label>
											<div id="summernote">
											</div>
										</div>
									</div>
									<div
										class="modal-satus-toggle d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user4" class="check" checked="">
											<label for="user4" class="checktoggle"> </label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Submit</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Email Template -->

		<!-- Edit Email Template -->
		<div class="modal fade" id="edit_email_template">
			<div class="modal-dialog modal-dialog-centered  modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Email Template</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('email-templates')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Title</label>
										<input type="text" class="form-control" placeholder="Enter Title"
											value="Email Verification">
									</div>
									<div class="mb-3">
										<label class="form-label">Subject</label>
										<input type="text" class="form-control" placeholder="Enter Subject"
											value="English">
									</div>
									<div class="mb-3">
										<label class="form-label">Template Content</label>
										<div class="input-blocks summer-description-box notes-summernote">
											<label class="form-label">Descriptions</label>
											<div id="summernote2">
												<p>Hi [User's Name],
													Welcome to Preskool ! We're thrilled to have you on board. Get
													ready to experience a seamless and efficient way.
													To get started, Click here
													If you have any questions or need assistance, feel free to reach
													out to our support team at [support@example.com].</p>
											</div>
										</div>
									</div>
									<div
										class="modal-satus-toggle d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user5" class="check" checked="">
											<label for="user5" class="checktoggle"> </label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Edit</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Email Template -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('email-templates')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->
@endif

@if (Route::is(['sms-settings']))
   <!-- Connect Sms -->
		<div class="modal fade" id="connect_sms">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Nexmo</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('sms-settings')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">API Key</label>
										<input type="text" class="form-control" placeholder="Enter API Key">
									</div>
									<div class="mb-3">
										<label class="form-label">API Secret Key</label>
										<input type="text" class="form-control" placeholder="Enter API Secret Key">
									</div>
									<div class="mb-3">
										<label class="form-label">Sender ID</label>
										<input type="text" class="form-control" placeholder="Enter Sender ID">
									</div>
									<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user4" class="check" checked="">
											<label for="user4" class="checktoggle"> </label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Submit</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Connect Sms -->
@endif

@if (Route::is(['payment-gateways']))
   <!-- Add Payment Gateway -->
		<div class="modal fade" id="connect_payment">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Paypal</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('payment-gateways')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Email Address</label>
										<input type="text" class="form-control" placeholder="Enter Email Address">
									</div>
									<div class="mb-3">
										<label class="form-label">API Key</label>
										<input type="text" class="form-control" placeholder="Enter API Key">
									</div>
									<div class="mb-3">
										<label class="form-label">Secret Key</label>
										<input type="text" class="form-control" placeholder="Enter Secret Key">
									</div>
									<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user16" class="check" checked="">
											<label for="user16" class="checktoggle"> </label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Submit</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Payment Gateway -->
@endif

@if (Route::is(['tax-rates']))
    <!-- Add Tax Rate -->
		<div class="modal fade" id="add_tax_rate">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Tax Rate</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('tax-rates')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Name</label>
										<input type="text" class="form-control" placeholder="Enter Name">
									</div>
									<div class="mb-3">
										<label class="form-label">Tax Rate %</label>
										<input type="text" class="form-control" placeholder="Enter Tax Rate">
									</div>
									<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user1" class="check">
											<label for="user1" class="checktoggle"> </label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Tax Rate</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Tax Rate -->

		<!-- Edit Tax Rate -->
		<div class="modal fade" id="edit_tax_rate">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Tax Rate</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('tax-rates')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Name</label>
										<input type="text" class="form-control" placeholder="Enter Name">
									</div>
									<div class="mb-3">
										<label class="form-label">Tax Rate %</label>
										<input type="text" class="form-control" placeholder="Enter Tax Rate" value="5%">
									</div>
									<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user2" class="check" checked="">
											<label for="user2" class="checktoggle"> </label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Tax Rate -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('tax-rates')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->
@endif

@if(Route::is(['religion']))
   <!-- Add Religion -->
		<div class="modal fade" id="add_religion">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Religion</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('religion')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Name</label>
										<input type="text" class="form-control" placeholder="Enter Name">
									</div>
									<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user4" class="check">
											<label for="user4" class="checktoggle"> </label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Religion</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Religion -->

		<!-- Edit Religion -->
		<div class="modal fade" id="edit_religion">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Religion</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('religion')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Name</label>
										<input type="text" class="form-control" placeholder="Enter Name">
									</div>
									<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user5" class="check" checked>
											<label for="user5" class="checktoggle"> </label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Religion -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('religion')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->
@endif

@if(Route::is(['storage']))
  <!-- Connect Sms -->
  <div class="modal fade" id="connect_sms">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">AWS Settings</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('storage')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">AWS Access Key</label>
                                <input type="text" class="form-control" placeholder="Enter AWS Access Key">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Secret Key</label>
                                <input type="text" class="form-control" placeholder="Enter Secret Key">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bucket Name</label>
                                <input type="text" class="form-control" placeholder="Enter Bucket Name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Region</label>
                                <input type="text" class="form-control" placeholder="Enter Region">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Base URL</label>
                                <input type="text" class="form-control" placeholder="Enter Base URL">
                            </div>
                            <div class="modal-satus-toggle d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="status-toggle modal-status">
                                    <input type="checkbox" id="user4" class="check" checked="">
                                    <label for="user4" class="checktoggle"> </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Connect Sms -->

@endif

@if(Route::is(['ban-ip-address']))
   <!-- Add Ban Ip -->
		<div class="modal fade" id="add_ban_ip">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Ban IP Address</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('ban-ip-address')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="col-form-label">IP Address</label>
										<input type="text" class="form-control" placeholder="Enter IP Address">
									</div>
									<div class="mb-0">
										<label class="col-form-label">Reason</label>
										<textarea rows="4" class="form-control" placeholder="Add Comment"></textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add IP Address</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Ban Ip -->

		<!-- Edit Ban Ip -->
		<div class="modal fade" id="edit_ban_ip">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Ban IP Address</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('ban-ip-address')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="col-form-label">IP Address</label>
										<input type="text" class="form-control" placeholder="Enter IP Address" value="198.120.16.01">
									</div>
									<div class="mb-0">
										<label class="col-form-label">Reason</label>
										<textarea rows="4" class="form-control" placeholder="Add Comment">Temporarily block to protect user accounts from internet fraudsters.</textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Ban Ip -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('ban-ip-address')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

@endif

@if (Route::is(['countries']))
 <!-- Add Country -->
 <div class="modal fade" id="add_country">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Country</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('countries')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Country Name</label>
                                <input type="text" class="form-control" placeholder="Enter Country Name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Country Code</label>
                                <input type="text" class="form-control" placeholder="Enter Country Code">
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Country</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Country -->

<!-- Edit Country -->
<div class="modal fade" id="edit_country">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Country</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('countries')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Country Name</label>
                                <input type="text" class="form-control" placeholder="Enter Country Name" value="United States">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Country Code</label>
                                <input type="text" class="form-control" placeholder="Enter Country Code" value="US">
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Edit Country -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('countries')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->

@endif

@if (Route::is(['states']))
   <!-- Add State -->
		<div class="modal fade" id="add_state">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add State</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('states')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">State Name</label>
										<input type="text" class="form-control" >
									</div>
									<div class="mb-3">
										<label class="form-label">Country Name</label>
										<select class="select">
											<option>Select</option>
											<option>United States</option>
											<option>Germany</option>
											<option>Canada</option>
											<option>Australia</option>
										</select>
									</div>
									<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user1" class="check">
											<label for="user1" class="checktoggle"> </label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add State</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add State -->

		<!-- Edit State -->
		<div class="modal fade" id="edit_state">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit State</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('states')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">State Name</label>
										<input type="text" class="form-control" placeholder="Enter State Name"
											value="California">
									</div>
									<div class="mb-3">
										<label class="form-label">Country Name</label>
										<select class="select">
											<option>Select</option>
											<option selected>United States</option>
											<option>Germany</option>
											<option>Canada</option>
											<option>Australia</option>
										</select>
									</div>
									<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="status-toggle modal-status">
											<input type="checkbox" id="user2" class="check" checked="">
											<label for="user2" class="checktoggle"> </label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit State -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('states')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3"
									data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

@endif

@if (Route::is(['cities']))
   <!-- Add Cities -->
		<div class="modal fade" id="add_cities">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add City</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('cities')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">City Name</label>
										<input type="text" class="form-control" placeholder="Enter City Name">
									</div>
									<div class="mb-3">
										<label class="form-label">State Name</label>
										<select class="select">
											<option>Select</option>
											<option>California</option>
											<option>New York</option>
											<option>Texas</option>
											<option>Bavaria</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Country Name</label>
										<select class="select">
											<option>Select</option>
											<option>United States</option>
											<option>Germany</option>
											<option>Canada</option>
											<option>Australia</option>
										</select>
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="form-check form-switch">
											<input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add City</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Cities -->

		<!-- Edit Cities -->
		<div class="modal fade" id="edit_city">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit City</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('cities')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">City Name</label>
										<input type="text" class="form-control" placeholder="Enter State Name"
											value="Los Angeles">
									</div>
									<div class="mb-3">
										<label class="form-label">State Name</label>
										<select class="select">
											<option>Select</option>
											<option selected>Los Angeles</option>
											<option>New York</option>
											<option>Texas</option>
											<option>Bavaria</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Country Name</label>
										<select class="select">
											<option>Select</option>
											<option selected>United States</option>
											<option>Germany</option>
											<option>Canada</option>
											<option>Australia</option>
										</select>
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="form-check form-switch">
											<input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Cities -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('cities')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3"
									data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->
@endif

@if (Route::is(['testimonials']))
   		<!-- Add Testimonials -->
		<div class="modal fade" id="add_testimonials">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Testimonial</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('testimonials')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Author</label>
										<select class="select">
											<option>Select</option>
											<option>Thomas</option>
											<option>Claudia</option>
											<option>Robert</option>
											<option>Michael</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Role</label>
										<select class="select">
											<option>Select</option>
											<option>Parent</option>
											<option>Teacher</option>
											<option>Student</option>
											<option>Admin</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Date Added</label>
										<div class="date-pic">
											<input type="text" class="form-control datetimepicker" placeholder="Select">
											<span class="cal-icon"><i class="ti ti-calendar"></i></span>
										</div>
									</div>
									<div class="mb-0">
										<label class="form-label">Content</label>
										<textarea placeholder="Add Comment" class="form-control" rows="4"></textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Testimoial</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Add Testimonials -->

		<!-- Edit Testimonials -->
		<div class="modal fade" id="edit_testimonials">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Testimonial</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('testimonials')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">

									<div class="mb-3">
										<label class="form-label">Author</label>
										<select class="select">
											<option>Select</option>
											<option selected>Thomas</option>
											<option>Claudia</option>
											<option>Robert</option>
											<option>Michael</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Role</label>
										<select class="select">
											<option selected>Select</option>
											<option>Parent</option>
											<option>Teacher</option>
											<option>Student</option>
											<option>Admin</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Date Added</label>
										<div class="date-pic">
											<input type="text" class="form-control datetimepicker" placeholder="Select">
											<span class="cal-icon"><i class="ti ti-calendar"></i></span>
										</div>
									</div>
									<div class="mb-0">
										<label class="form-label">Content</label>
										<textarea placeholder="Add Comment" class="form-control" rows="4"></textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Edit Testimonials -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('testimonials')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3"
									data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

@endif

@if (Route::is(['faq']))
   <!-- Add FAQ -->
		<div class="modal fade" id="add_faq">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add FAQ</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('faq')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Category</label>
										<input type="text" class="form-control" placeholder="Enter Category">
									</div>
									<div class="mb-3">
										<label class="form-label">Question</label>
										<textarea placeholder="Add Question" class="form-control" rows="4"></textarea>
									</div>
									<div class="mb-0">
										<label class="form-label">Answer</label>
										<textarea placeholder="Add Answer" class="form-control" rows="4"></textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add FAQ</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add FAQ -->

		<!-- Edit FAQ -->
		<div class="modal fade" id="edit_faq">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit FAQ</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('faq')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Category</label>
										<input type="text" class="form-control" placeholder="Enter Category">
									</div>
									<div class="mb-3">
										<label class="form-label">Question</label>
										<textarea placeholder="How do I reset my password?"
											value="How do I reset my password?" class="form-control"
											rows="4"></textarea>
									</div>
									<div class="mb-0">
										<label class="form-label">Answer</label>
										<textarea placeholder="You can reset your password from the login page."
											value="You can reset your password from the login page."
											class="form-control" rows="4"></textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit FAQ -->


		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('faq')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3"
									data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->
	
@endif
@if (Route::is(['parent-dashboard']))
<!-- Edit Parent -->
<div class="modal fade" id="edit_parent">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Parent</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('parents')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center upload-pic flex-wrap row-gap-3 mb-3">
                                <div
                                    class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                                    <i class="ti ti-photo-plus fs-16"></i>
                                </div>
                                <div class="profile-upload">
                                    <div class="profile-uploader d-flex align-items-center">
                                        <div class="drag-upload-btn mb-3">
                                            Upload
                                            <input type="file" class="form-control image-sign" multiple="">
                                        </div>
                                        <a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
                                    </div>
                                    <p>Upload image size 4MB, Format JPG, PNG, SVG</p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" placeholder="Enter Name" value="Thomas">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" placeholder="Enter Phone Number"
                                    value="+1 65738 58937">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="text" class="form-control" placeholder="Enter Email Address"
                                    value="thomas@example.com">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Child</label>
                                <select class="select" multiple>
                                    <option selected>Janet</option>
                                    <option selected>Joann</option>
                                    <option>Kathleen</option>
                                    <option>Gifford</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Edit Parent -->
@endif
@if (Route::is(['student-dashboard']))
<!-- Add Exam Schedule -->
<div class="modal fade" id="add_exam_schedule">
    <div class="modal-dialog modal-dialog-centered  modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Exam Schedule</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('student-dashboard')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Class</label>
                                        <input type="text" class="form-control" placeholder="Enter Class">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Section</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>A</option>
                                            <option>B</option>
                                            <option>C</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Exam Name</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>Week text</option>
                                            <option>Mothly Test</option>
                                            <option>Chapter Wise Test</option>
                                            <option>Unit Test</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Start Time</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>09:30 AM</option>
                                            <option>10:30 AM</option>
                                            <option>11:00 AM</option>
                                            <option>12:30 PM</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">End Time</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>10:45 AM</option>
                                            <option>11:00 AM</option>
                                            <option>11:30 AM</option>
                                            <option>12:00 PM</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Duration(min)</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>3 hrs</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="exam-schedule-add">	
                        <div class="exam-schedule-row d-flex align-items-center flex-wrap column-gap-3">
                            <div class="shedule-info flex-fill">
                                <div class="mb-3">
                                    <label class="form-label">Exam Date</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>13 May 2024 </option>
                                        <option>14 May 2024  </option>
                                        <option>15 May 2024  </option>
                                    </select>
                                </div>
                            </div>
                            <div class="shedule-info flex-fill">
                                <div class="mb-3">
                                    <label class="form-label">Subject</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>English</option>
                                        <option>Spanish</option>
                                        <option>Physics</option>
                                    </select>
                                </div>
                            </div>
                            <div class="shedule-info flex-fill">
                                <div class="mb-3">
                                    <label class="form-label">Room No</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>101</option>
                                        <option>103</option>
                                        <option>104</option>
                                    </select>
                                </div>
                            </div>
                            <div class="shedule-info flex-fill">
                                <div class="mb-3">
                                    <label class="form-label">Max Marks</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>100</option>
                                    </select>
                                </div>
                            </div>
                            <div class="shedule-info flex-fill">
                                <div class="d-flex align-items-end">
                                    <div class="mb-3 flex-fill">
                                        <label class="form-label">Min Marks</label>
                                        <select class="select">
                                            <option>Select</option>
                                            <option>35</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 ms-2">
                                        <a href="#" class="delete-schedule-table"><i class="ti ti-trash"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="javascript:void(0);" class="btn btn-primary add-new-schedule"><i class="ti ti-square-rounded-plus-filled me-2"></i>Add New</a>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Exam Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Exam Schedule -->
@endif
@if (Route::is(['teacher-dashboard']))
 <!-- Add Event -->
 <div class="modal fade" id="add_event">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">New Event</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>					
            <form action="{{url('teacher-dashboard')}}">
                <div class="modal-body">		
                    <div class="row">
                        <div class="col-md-12">
                            <div>
                                <label class="form-label">Event For</label>
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="all" checked="">
                                        <label class="form-check-label" for="all">
                                            All
                                        </label>
                                    </div>
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="students">
                                        <label class="form-check-label" for="students">
                                            Students
                                        </label>
                                    </div>
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="staffs">
                                        <label class="form-check-label" for="staffs">
                                            Staffs
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="all-content" id="all-student">
                                <div class="mb-3">
                                    <label class="form-label">Classes</label>
                                    <select class="select">
                                        <option>All Classes</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sections</label>
                                    <select class="select">
                                        <option>All Sections</option>
                                        <option>A</option>
                                        <option>B</option>
                                    </select>
                                </div>
                            </div>
                            <div class="all-content" id="all-staffs">
                                <div class="mb-3">
                                    <div class="bg-light-500 p-3 pb-2 rounded">
                                        <label class="form-label">Role</label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Admin
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox" checked>Teacher
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Driver
                                                </div>										
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Accountant
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Librarian
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Receptionist
                                                </div>											
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">All Teachers</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                            </div>
                        </div>			
                        <div class="mb-3">
                            <label class="form-label">Event Title</label>
                            <input type="text" class="form-control" placeholder="Enter Title">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event Category</label>
                            <select class="select">
                                <option>Select</option>
                                <option>Celebration</option>
                                <option>Training</option>
                                <option>Meeting</option>
                                <option>Holidays</option>
                            </select>
                        </div>						
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="15 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>											
                        </div>									
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="21 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>											
                        </div>								
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Time</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control timepicker" placeholder="09:10 AM">
                                    <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                </div>
                            </div>											
                        </div>							
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Time</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control timepicker" placeholder="12:50 PM">
                                    <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                </div>
                            </div>											
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="bg-light p-3 pb-2 rounded">
                                    <div class="mb-3">
                                        <label class="form-label">Attachment</label>
                                        <p>Upload size of 4MB, Accepted Format PDF</p>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap">
                                        <div class="btn btn-primary drag-upload-btn mb-2 me-2">
                                            <i class="ti ti-file-upload me-1"></i>Upload
                                            <input type="file" class="form-control image_sign" multiple="">
                                        </div>
                                        <p class="mb-2">Fees_Structure.pdf</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" rows="4">Meeting with Staffs on the Quality Improvement s and completion of syllabus before the August,  enhance the students health issue</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Event -->	
@endif
@if (Route::is(['index']))
<!-- Add Class Routine -->
<div class="modal fade" id="add_class_routine">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-wrapper">
                <div class="modal-header">
                    <h4 class="modal-title">Add Class Routine</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('index')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Teacher</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Erickson</option>
                                        <option>Mori</option>
                                        <option>Joseph</option>
                                        <option>James</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Class</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Section</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>A</option>
                                        <option>B</option>
                                        <option>C</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Day</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Monday</option>
                                        <option>Tuesday</option>
                                        <option>Wedneshday</option>
                                        <option>Thursday</option>
                                        <option>Friday</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Start Time</label>
                                            <div class="date-pic">
                                                <input type="text" class="form-control timepicker"
                                                    placeholder="Choose">
                                                <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">End Time</label>
                                            <div class="date-pic">
                                                <input type="text" class="form-control timepicker"
                                                    placeholder="Choose">
                                                <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Class Room</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>101</option>
                                        <option>102</option>
                                        <option>103</option>
                                        <option>104</option>
                                        <option>105</option>
                                    </select>
                                </div>
                                <div
                                    class="modal-satus-toggle d-flex align-items-center justify-content-between">
                                    <div class="status-title">
                                        <h5>Status</h5>
                                        <p>Change the Status by toggle </p>
                                    </div>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user1" class="check">
                                        <label for="user1" class="checktoggle"> </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Class Routine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- /Add Class Routine -->

<!-- Add Event -->
<div class="modal fade" id="add_event">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">New Event</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>					
            <form action="{{url('index')}}">
                <div class="modal-body">		
                    <div class="row">
                        <div class="col-md-12">
                            <div>
                                <label class="form-label">Event For</label>
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="all" checked="">
                                        <label class="form-check-label" for="all">
                                            All
                                        </label>
                                    </div>
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="students">
                                        <label class="form-check-label" for="students">
                                            Students
                                        </label>
                                    </div>
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="staffs">
                                        <label class="form-check-label" for="staffs">
                                            Staffs
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="all-content" id="all-student">
                                <div class="mb-3">
                                    <label class="form-label">Classes</label>
                                    <select class="select">
                                        <option>All Classes</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sections</label>
                                    <select class="select">
                                        <option>All Sections</option>
                                        <option>A</option>
                                        <option>B</option>
                                    </select>
                                </div>
                            </div>
                            <div class="all-content" id="all-staffs">
                                <div class="mb-3">
                                    <div class="bg-light-500 p-3 pb-2 rounded">
                                        <label class="form-label">Role</label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Admin
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox" checked>Teacher
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Driver
                                                </div>										
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Accountant
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Librarian
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Receptionist
                                                </div>											
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">All Teachers</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                            </div>
                        </div>			
                        <div class="mb-3">
                            <label class="form-label">Event Title</label>
                            <input type="text" class="form-control" placeholder="Enter Title">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event Category</label>
                            <select class="select">
                                <option>Select</option>
                                <option>Celebration</option>
                                <option>Training</option>
                                <option>Meeting</option>
                                <option>Holidays</option>
                            </select>
                        </div>						
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="15 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>											
                        </div>									
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="21 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>											
                        </div>								
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Time</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control timepicker" placeholder="09:10 AM">
                                    <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                </div>
                            </div>											
                        </div>							
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Time</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control timepicker" placeholder="12:50 PM">
                                    <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                </div>
                            </div>											
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="bg-light p-3 pb-2 rounded">
                                    <div class="mb-3">
                                        <label class="form-label">Attachment</label>
                                        <p>Upload size of 4MB, Accepted Format PDF</p>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap">
                                        <div class="btn btn-primary drag-upload-btn mb-2 me-2">
                                            <i class="ti ti-file-upload me-1"></i>Upload
                                            <input type="file" class="form-control image_sign" multiple="">
                                        </div>
                                        <p class="mb-2">Fees_Structure.pdf</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" rows="4">Meeting with Staffs on the Quality Improvement s and completion of syllabus before the August,  enhance the students health issue</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Event -->	
@endif
@if (Route::is(['ticket-grid']))
	<!-- Ticket View -->
    <div class="offcanvas offcanvas-end custom-offcanvas" tabindex="-1" id="ticket-reply">
        <div class="offcanvas-header border-bottom d-flex align-items-center justify-content-between p-3">
            <div class="d-flex align-items-center">
                <h5 class="me-2 mb-2">Laptop Issue</h5>
                <div class="dropdown me-1 mb-2 ms-1">
                    <a href="javascript:void(0);" class="dropdown-toggle  badge bg-outline-danger fs-12 text-danger d-inline-flex align-items-center p-1" data-bs-toggle="dropdown">
                        Open
                    </a>
                      <ul class="dropdown-menu p-3">
                         <li>
                             <a href="javascript:void(0);" class="dropdown-item rounded-1">Open</a>
                          </li>
                          <li>
                              <a href="javascript:void(0);" class="dropdown-item rounded-1">Closed</a>
                          </li>
                          <li>
                              <a href="javascript:void(0);" class="dropdown-item rounded-1">Reopened</a>
                          </li>
                      </ul>	
                </div> 
            </div>
            <div class="d-flex align-items-center">
                <h5 class="text-primary me-2">IT Support</h5>
                <button type="button" class="close-btn" data-bs-dismiss="offcanvas" aria-label="Close"><i class="ti ti-x"></i></button>
            </div>
        </div> 
        <div class="offcanvas-body p-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap border-bottom p-3 pb-0">
                <div class="d-block mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-pending rounded-pill me-3">#TK0003</span>
                        <span class="badge badge-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Low</span>
                    </div>
                    <div class="d-flex align-items-center flex-wrap">
                        <p class="text-gray d-flex align-items-center me-2 mb-1">
                            <img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" class="avatar avatar-xs rounded-circle me-2" alt="img">
                            Assigned to <span class="text-dark ms-1"> Teresa</span>
                        </p>
                        <p class="text-gray d-flex align-items-center mb-1 me-2"><i class="ti ti-calendar-bolt me-1"></i>Updated 22 hours ago</p>
                    </div>
                </div>
                <div class="mb-3">
                    <select class="select">
                        <option>Mark as Public</option>
                        <option selected>Mark as Private</option>
                    </select>
                </div>
            </div>
            <div class="border-bottom p-3 pb-0">
                <div class="d-flex">
                    <span class="avatar avatar-xxl flex-shrink-0 me-4 mb-3"><img src="{{URL::asset('build/img/students/student-11.jpg')}}" alt=""></span>
                    <div>
                        <div class="alert bg-dark rounded d-flex align-items-center justify-content-between mb-3" role="alert">
                            <p class="mb-0">Note! This ticket is closed. If you want to re-open it, just post a reply below.</p>
                            <button type="button" class="btn-close opacity-100 text-white p-0" data-bs-dismiss="alert" aria-label="Close"><span><i class="ti ti-x"></i></span></button>
                        </div>
                        <div class="mb-3">
                            <div class="summernote">Write a new comment, send your team notification by typing @ followed by their name</div>
                            <div class="d-flex align-items-center justify-content-between mt-3">
                                <a href="javascript:void(0);" class="btn btn-light"><i class="ti ti-pin"></i>Attachment</a>
                                <a href="javascript:void(0);" class="btn btn-primary">Post Comment</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-bottom p-3 pb-0">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar avatar-xl me-2 flex-shrink-0"><img src="{{URL::asset('build/img/students/student-11.jpg')}}" alt=""></span>
                    <div>
                        <h6 class="mb-1">James Hendriques</h6>
                        <p><i class="ti ti-calendar-bolt me-1"></i>Updated 5 hours ago</p>
                    </div>
                </div>
                <div>
                    <div class="mb-3">
                        <h5 class="mb-1">Impact on Work</h5>
                        <p>This issue disrupts meetings, delays task completion, and affects my overall productivity.</p>
                    </div>
                    <span class="badge bg-light">Screenshot.png<i class="ti ti-download ms-1"></i></span>
                    <div class="d-flex align-items-center my-3">
                        <a href="#" class="d-inline-flex align-items-center text-primary fw-medium me-3"><i class="ti ti-reload me-1"></i>Reply</a>
                        <p class="text-gray d-flex align-items-center"><i class="ti ti-message-share me-1"></i>9 Comments</p>
                    </div>
                    <div class="mb-3">
                        <div class="summernote">Write a new comment, send your team notification by typing @ followed by their name</div>
                        <div class="d-flex align-items-center justify-content-between mt-3">
                            <a href="javascript:void(0);" class="btn btn-light"><i class="ti ti-pin"></i>Attachment</a>
                            <a href="javascript:void(0);" class="btn btn-primary">Post Comment</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-bottom p-3 pb-0">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar avatar-xl me-2 flex-shrink-0"><img src="{{URL::asset('build/img/profiles/avatar-19.jpg')}}" alt=""></span>
                    <div>
                        <h6 class="mb-1">Support Agent Angio</h6>
                        <p><i class="ti ti-calendar-bolt me-1"></i>Updated 5 hours ago</p>
                    </div>
                </div>
                <div>
                    <div class="mb-3">
                        <p>Switch on the side panel & update the OS,  Login in to the device manager and update the password</p>
                    </div>
                    <div class="d-flex align-items-center my-3">
                        <a href="#" class="d-inline-flex align-items-center text-primary fw-medium me-3"><i class="ti ti-reload me-1"></i>Reply</a>
                        <p class="text-gray d-flex align-items-center"><i class="ti ti-message-share me-1"></i>5 Comments</p>
                    </div>
                </div>
            </div>
            <div class="border-bottom p-3 pb-0">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar avatar-xl me-2 flex-shrink-0"><img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" alt=""></span>
                    <div>
                        <h6 class="mb-1">Marilyn Siegle</h6>
                        <p><i class="ti ti-calendar-bolt me-1"></i>Updated 6 hours ago</p>
                    </div>
                </div>
                <div>
                    <div class="mb-3">
                        <p>Check the System and Application logs in the Event Viewer for warnings or errors that coincide with the times the freezes occur.</p>
                    </div>
                    <div class="d-flex align-items-center my-3">
                        <a href="#" class="d-inline-flex align-items-center text-primary fw-medium me-3"><i class="ti ti-reload me-1"></i>Reply</a>
                        <p class="text-gray d-flex align-items-center"><i class="ti ti-message-share me-1"></i>7 Comments</p>
                    </div>
                </div>
            </div>	
            <div class="p-3 pb-0">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar avatar-xl me-2 flex-shrink-0"><img src="{{URL::asset('build/img/profiles/avatar-22.jpg')}}" alt=""></span>
                    <div>
                        <h6 class="mb-1">Brian Foust</h6>
                        <p><i class="ti ti-calendar-bolt me-1"></i>Updated 8 hours ago</p>
                    </div>
                </div>
                <div>
                    <div class="mb-3">
                        <p>Check the System and Application logs in the Event Viewer for warnings or errors that coincide with the times the freezes occur.</p>
                    </div>
                    <div class="d-flex align-items-center my-3">
                        <a href="#" class="d-inline-flex align-items-center text-primary fw-medium me-3"><i class="ti ti-reload me-1"></i>Reply</a>
                        <p class="text-gray d-flex align-items-center"><i class="ti ti-message-share me-1"></i>9 Comments</p>
                    </div>
                </div>
            </div>		
        </div>
    </div>
    <!-- /Ticket View -->

    <!-- Add Ticket -->
    <div class="modal fade" id="add_ticket">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Ticket</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>					
                <form action="{{url('ticket-details')}}">
                    <div class="modal-body">		
                        <div class="row">		
                            <div class="col-md-12">		
                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-control" placeholder="Enter Title">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Event Category</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Internet Issue</option>
                                        <option>Redistribute</option>
                                        <option>Computer</option>
                                        <option>Complaint</option>
                                    </select>
                                </div>			
                                <div class="mb-3">
                                    <label class="form-label">Subject</label>
                                    <input type="text" class="form-control" placeholder="Enter Subject">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Assign To</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Teresa</option>
                                        <option>James</option>
                                        <option>Daniel</option>
                                        <option>Jacquelin</option>
                                    </select>
                                </div>				
                                <div class="mb-3">
                                    <label class="form-label">Ticket Description</label>
                                    <textarea class="form-control" placeholder="Add Question"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Priority</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>High</option>
                                        <option>Low</option>
                                        <option>Medium</option>
                                    </select>
                                </div>		
                                <div class="mb-0">
                                    <label class="form-label">Status</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Closed</option>
                                        <option>Reopened</option>
                                        <option>Inprogress</option>
                                    </select>
                                </div>	
                            </div>		
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Add Ticket -->
@endif
@if (Route::is(['ticket-details']))
<!-- Add Ticket -->
<div class="modal fade" id="add_ticket">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Ticket</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>					
            <form action="{{url('ticket-grid')}}">
                <div class="modal-body">		
                    <div class="row">		
                        <div class="col-md-12">		
                            <div class="mb-3">
                                <label class="col-form-label">Title</label>
                                <input type="text" class="form-control" placeholder="Enter Title">
                            </div>
                            <div class="mb-3">
                                <label class="col-form-label">Event Category</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Internet Issue</option>
                                    <option>Redistribute</option>
                                    <option>Computer</option>
                                    <option>Complaint</option>
                                </select>
                            </div>			
                            <div class="mb-3">
                                <label class="col-form-label">Subject</label>
                                <input type="text" class="form-control" placeholder="Enter Subject">
                            </div>
                            <div class="mb-3">
                                <label class="col-form-label">Assign To</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Teresa</option>
                                    <option>James</option>
                                    <option>Daniel</option>
                                    <option>Jacquelin</option>
                                </select>
                            </div>				
                            <div class="mb-3">
                                <label class="col-form-label">Ticket Description</label>
                                <textarea class="form-control" placeholder="Add Question"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="col-form-label">Priority</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>High</option>
                                    <option>Low</option>
                                    <option>Medium</option>
                                </select>
                            </div>		
                            <div class="mb-0">
                                <label class="col-form-label">Status</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Closed</option>
                                    <option>Reopened</option>
                                    <option>Inprogress</option>
                                </select>
                            </div>	
                        </div>		
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Ticket -->

@endif
@if (Route::is(['tickets']))
   	<!-- Add Ticket -->
		<div class="modal fade" id="add_ticket">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Ticket</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>					
					<form action="{{url('tickets')}}">
						<div class="modal-body">		
							<div class="row">		
								<div class="col-md-12">		
									<div class="mb-3">
										<label class="form-label">Title</label>
										<input type="text" class="form-control">
									</div>
									<div class="mb-3">
										<label class="form-label">Event Category</label>
										<select class="select">
											<option>Select</option>
											<option>Internet Issue</option>
											<option>Redistribute</option>
											<option>Computer</option>
											<option>Complaint</option>
										</select>
									</div>			
									<div class="mb-3">
										<label class="form-label">Subject</label>
										<input type="text" class="form-control" >
									</div>
									<div class="mb-3">
										<label class="form-label">Assign To</label>
										<select class="select">
											<option>Select</option>
											<option>Teresa</option>
											<option>James</option>
											<option>Daniel</option>
											<option>Jacquelin</option>
										</select>
									</div>				
									<div class="mb-3">
										<label class="form-label">Ticket Description</label>
										<textarea class="form-control"></textarea>
									</div>
									<div class="mb-3">
										<label class="form-label">Priority</label>
										<select class="select">
											<option>Select</option>
											<option>High</option>
											<option>Low</option>
											<option>Medium</option>
										</select>
									</div>		
									<div class="mb-0">
										<label class="form-label">Status</label>
										<select class="select">
											<option>Select</option>
											<option>Closed</option>
											<option>Reopened</option>
											<option>Inprogress</option>
										</select>
									</div>	
								</div>		
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Ticket</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Ticket -->	

@endif
@if(Route::is(['contact-messages']))
<!-- Add Contact Message -->
<div class="modal fade" id="add_messages">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Contact Messages</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('contact-messages')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" placeholder="Enter name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control" placeholder="Enter Email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" placeholder="Enter Phone Number">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <input type="text" class="form-control" placeholder="Enter Message">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="text" class="form-control" placeholder="Enter Date">
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add messages</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Contact Message -->

<!-- Edit Contact Message -->
<div class="modal fade" id="edit_messages">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Contact Messages</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('contact-messages')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" placeholder="Enter name" value="Teresa">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control" placeholder="Enter Email"
                                    value="teresa@example.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" placeholder="Enter Phone Number"
                                    value="+1 82392 37359	">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <input type="text" class="form-control" placeholder="Enter Message"
                                    value="Reminder: Staff meeting tomorrow.">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="text" class="form-control" placeholder="Enter Date"
                                    value="25 Mar 2024">
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Cganges</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Contact Message -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('contact-messages')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->


@endif
@if(Route::is(['profile']))
	<!-- Edit Profile -->
    <div class="modal fade" id="edit_personal_information">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Personal Information</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('profile')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" placeholder="Enter First Name">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Last Name">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">User Name</label>
                                    <input type="text" class="form-control" placeholder="Enter User Name">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="text" class="form-control" placeholder="Enter Email">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" placeholder="Enter Phone Number">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Bio</label>
                                    <input type="text" class="form-control" placeholder="Enter Bio">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Profile -->

    <!-- Edit Profile -->
    <div class="modal fade" id="edit_address_information">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Address Information</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('profile')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" placeholder="Enter Address">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Country</label>
                                    <input type="text" class="form-control" placeholder="Enter Country">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">State/Province</label>
                                    <input type="text" class="form-control" placeholder="Enter State/Province">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" placeholder="Enter City">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" placeholder="Enter Postal Code">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Profile -->

    <!-- Change Password -->
    <div class="modal fade" id="change_password">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Change Password</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('profile')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <div class="pass-group d-flex">
                                        <input type="password" class="pass-input form-control">
                                        <span class="ti toggle-password ti-eye-off"></span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <div class="pass-group d-flex">
                                        <input type="password" class="pass-inputs form-control">
                                        <span class="ti toggle-passwords ti-eye-off"></span>
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Confirm Password</label>
                                    <div class="pass-group d-flex">
                                        <input type="password" class="pass-inputa form-control">
                                        <span class="ti toggle-passworda ti-eye-off"></span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Change Password -->
@endif
@if(Route::is(['class-syllabus']))
  <!-- Add Syllabus -->
  <div class="modal fade" id="add_syllabus">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Subject Group</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('class-syllabus')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Class</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>I</option>
                                    <option>II</option>
                                    <option>III</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Section</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>A</option>
                                    <option>B</option>
                                    <option>C</option>
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Subject Group</label>
                                <input type="text" class="form-control" placeholder="Enter Subject Group">
                            </div>
                        </div>
                    </div>
                </div>						
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Subject Group</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Syllabus -->

<!-- Edit Syllabus -->
<div class="modal fade" id="edit_syllabus">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Subject Group</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('class-syllabus')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Class</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option selected>I</option>
                                    <option>II</option>
                                    <option>III</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Section</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option selected>A</option>
                                    <option>B</option>
                                    <option>C</option>
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Save Changes</label>
                                <input type="text" class="form-control" placeholder="Enter Subject Group"
                                    value="I, C English">
                            </div>
                        </div>
                    </div>
                </div>						
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Edit Syllabus	-->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('class-syllabus')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->
@endif

@if (Route::is(['academic-reasons']))
  <!-- Add Reason -->
  <div class="modal fade" id="add_reason">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Reason</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('academic-reasons')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Teacher</option>
                                    <option>Student</option>
                                    <option>Staff</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="switch-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Reason</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Reason -->

<!-- Edit Reason -->
<div class="modal fade" id="edit_reason">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Reason</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('academic-reasons')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" placeholder="Enter Name" value="Text">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option selected>Teacher</option>
                                    <option>Student</option>
                                    <option>Staff</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="switch-sm2">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Reason -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('academic-reasons')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->

@endif

@if(Route::is(['fees-group']))
<!-- Add Fees Group -->
<div class="modal fade" id="add_fees_group">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Fees Group</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('fees-group')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Fees Group</label>
                                <input type="text" class="form-control" placeholder="Enter Fees Group">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" rows="4"
                                    placeholder="Add Comment"></textarea>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Fees Group</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Fees Group -->

<!-- Edit Fees Group -->
<div class="modal fade" id="edit_fees_group">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Fees Group</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('fees-group')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Fees Group</label>
                                <input type="text" class="form-control" placeholder="Enter Fees Group"
                                    value="Tuition Fees">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" rows="4"
                                    placeholder="Add Comment">The money that you pay to be taught</textarea>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Fees Group -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('fees-group')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->

 @endif

@if(Route::is(['fees-type']))
<!-- Add Fees Type -->
<div class="modal fade" id="add_fees_Type">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Fees Type</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('fees-type')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" placeholder="Enter Name">
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label">Fees Group</label>
                                    <a href="#" class="text-primary" data-bs-toggle="modal"
                                        data-bs-target="#add_new_fees_group"><span><i
                                                class="ti ti-square-rounded-plus-filled"></i></span> Add
                                        New</a>
                                </div>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Tuition Fees</option>
                                    <option>Monthly Fees</option>
                                    <option>Class 1 General</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="4" placeholder="Add Comment"></textarea>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="status-title">
                                <h5>Status</h5>
                                <p>Change the Status by toggle </p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Fees Type</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Fees Type -->

<!-- Edit Fees Type -->
<div class="modal fade" id="edit_fees_Type">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Fees Type</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('fees-type')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" placeholder="Enter Name" value="">
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label">Fees Group</label>
                                    <a href="#" class="text-primary" data-bs-toggle="modal"
                                        data-bs-target="#add_new_fees_group"><span><i
                                                class="ti ti-square-rounded-plus-filled"></i></span> Add
                                        New</a>
                                </div>
                                <select class="select">
                                    <option>Select</option>
                                    <option selected>Tuition Fees</option>
                                    <option>Monthly Fees</option>
                                    <option>Class 1 General</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="4" placeholder="Add Comment"></textarea>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="status-title">
                                <h5>Status</h5>
                                <p>Change the Status by toggle </p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Fees Type -->

<!-- Add New Fees Type -->
<div class="modal fade" id="add_new_fees_group">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add New Fees Group</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('fees-type')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" placeholder="Enter Name" value="">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="4" placeholder="Add Comment"></textarea>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="status-title">
                                <h5>Status</h5>
                                <p>Change the Status by toggle </p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="switch-sm3">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Fees Type</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add New Fees Type -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('fees-type')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->		
@endif

@if (Route::is(['fees-master']))
    
		<!-- Add Fees Master -->
		<div class="modal fade" id="add_fees_master">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<div class="d-flex align-items-center">
							<h4 class="modal-title">Add Fees Master</h4>
							<span class="badge bg-soft-info ms-2">2024 - 2025</span>
						</div>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('fees-master')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Fees Group</label>
										<select class="select">
											<option>Select</option>
											<option>Admission-Fees</option>
											<option>Class 1 General</option>
											<option>Monthly Fees</option>
											<option>Class 1 Lump Sum</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Fees Type</label>
										<select class="select">
											<option>Select</option>
											<option>Tuition Fees</option>
											<option>Monthly Fees</option>
											<option>Admission Fees</option>
											<option>Bus Fees</option>
										</select>
									</div>
								</div>
								<div class="col-md-12">
									<div class="row">
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Due Date</label>
												<div class="date-pic">
													<input type="text" class="form-control datetimepicker"
														placeholder="Select">
													<span class="cal-icon"><i class="ti ti-calendar"></i></span>
												</div>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Amount</label>
												<input type="text" class="form-control" placeholder="Enter Amout">
											</div>
										</div>
									</div>
									<div class="mb-3">
										<label class="form-label">Fine Type</label>
										<div class="d-flex align-items-center check-radio-group">
											<label class="custom-radio">
												<input type="radio" name="radio" checked="">
												<span class="checkmark"></span>
												None
											</label>
											<label class="custom-radio percentage-radio">
												<input type="radio" name="radio">
												<span class="checkmark"></span>
												Percentage
											</label>
											<label class="custom-radio fixed-radio">
												<input type="radio" name="radio">
												<span class="checkmark"></span>
												Fixed
											</label>
										</div>
									</div>
									<div class="percentage-field">
										<div class="row">
											<div class="col-lg-6">
												<div class="mb-3">
													<label class="form-label">Percentage</label>
													<input type="text" class="form-control" placeholder="%">
												</div>
											</div>
											<div class="col-lg-6">
												<div class="mb-3">
													<label class="form-label">Amount ($)</label>
													<input type="text" class="form-control" placeholder="$">
												</div>
											</div>
										</div>
									</div>	
									<div class="fixed-field">
										<div class="row">
											<div class="col-lg-12">
												<div class="mb-3">
													<label class="form-label">Amount ($)</label>
													<input type="text" class="form-control" placeholder="$">
												</div>
											</div>
										</div>
									</div>	
								</div>
								<div class="d-flex align-items-center justify-content-between">
									<div class="status-title">
										<h5>Status</h5>
										<p>Change the Status by toggle </p>
									</div>
									<div class="form-check form-switch">
										<input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Fees Master</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Add Fees Master-->

		<!-- Edit Fees Master -->
		<div class="modal fade" id="edit_fees_master">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<div class="d-flex align-items-center">
							<h4 class="modal-title">Edit Fees Master</h4>
							<span class="badge bg-soft-info ms-2">2024 - 2025</span>
						</div>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('fees-master')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Fees Group</label>
										<select class="select">
											<option>Select</option>
											<option selected>Admission-Fees</option>
											<option>Class 1 General</option>
											<option>Monthly Fees</option>
											<option>Class 1 Lump Sum</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Fees Type</label>
										<select class="select">
											<option>Select</option>
											<option selected>Tuition Fees</option>
											<option>Monthly Fees</option>
											<option>Admission Fees</option>
											<option>Bus Fees</option>
										</select>
									</div>
								</div>
								<div class="col-md-12">
									<div class="row">
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Due Date</label>
												<div class="date-pic">
													<input type="text" class="form-control datetimepicker"
														placeholder="Select">
													<span class="cal-icon"><i class="ti ti-calendar"></i></span>
												</div>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mb-3">
												<label class="form-label">Amount</label>
												<input type="text" class="form-control" placeholder="Enter Amout" value="$500">
											</div>
										</div>
									</div>
									<div class="mb-3">
										<label class="form-label">Fine Type</label>
										<div class="d-flex align-items-center check-radio-group">
											<label class="custom-radio">
												<input type="radio" name="radio2" checked="">
												<span class="checkmark"></span>
												None
											</label>
											<label class="custom-radio percentage-radio">
												<input type="radio" name="radio2">
												<span class="checkmark"></span>
												Percentage
											</label>
											<label class="custom-radio fixed-radio">
												<input type="radio" name="radio2">
												<span class="checkmark"></span>
												Fixed
											</label>
										</div>
									</div>
									<div class="percentage-field">
										<div class="row">
											<div class="col-lg-6">
												<div class="mb-3">
													<label class="form-label">Percentage</label>
													<input type="text" class="form-control" placeholder="%">
												</div>
											</div>
											<div class="col-lg-6">
												<div class="mb-3">
													<label class="form-label">Amount ($)</label>
													<input type="text" class="form-control" placeholder="$">
												</div>
											</div>
										</div>
									</div>	
									<div class="fixed-field">
										<div class="row">
											<div class="col-lg-12">
												<div class="mb-3">
													<label class="form-label">Amount ($)</label>
													<input type="text" class="form-control" placeholder="$">
												</div>
											</div>
										</div>
									</div>	
								</div>
								<div class="d-flex align-items-center justify-content-between">
									<div class="status-title">
										<h5>Status</h5>
										<p>Change the Status by toggle </p>
									</div>
									<div class="form-check form-switch">
										<input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Edit Fees Master-->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('fees-master')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

@endif

@if (Route::is(['fees-assign']))
   <!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('fees-assign')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you
								delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3"
									data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

		<!-- Add Fees Assign -->
		<div class="modal fade" id="add_fees_assign">
			<div class="modal-dialog modal-dialog-centered  modal-xl">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Assign New Fees</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<div class="modal-body">
						<!-- Table Filter -->
						<div class="table-filter-head">
							<div class="filter-head-left">
								<h5>Search Criteria</h5>
								<div class="form-filter dropdown">
									<a href="javascript:void(0);" class="dropdown-toggle"><i
											class="ti ti-calendar-due me-2"></i>Fees Group</a>
									<div class="filter-dropdown-menu filter-drop-items">
										<form action="{{url('fees-assign')}}">
											<div class="filter-head-title">
												<h4>Select Fees Group</h4>
											</div>
											<div class="form-wrap icon-form">
												<span class="form-icon"><i class="ti ti-search"></i></span>
												<input type="text" class="form-control" placeholder="Search">
											</div>
											<div class="access-wrap">
												<ul>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox" checked>
															<span class="checkmarks"></span>
															<span class="name-id">
																Admission-Fees
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Class 1 General
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Monthly Fees
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox" checked>
															<span class="checkmarks"></span>
															<span class="name-id">
																Class 1 Lump Sum
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Class 1- I Installment
															</span>
														</label>
													</li>
												</ul>
											</div>
											<div class="d-flex drop-filter-btn">
												<a href="#" class="btn btn-outline-primary flex-fill"
													data-bs-dismiss="modal">Reset</a>
												<button type="submit"
													class="btn btn-primary flex-fill ms-3">Apply</button>
											</div>
										</form>
									</div>
								</div>
								<div class="form-filter dropdown">
									<a href="javascript:void(0);" class="dropdown-toggle"><i
											class="ti ti-calendar-due me-2"></i>Fees Type</a>
									<div class="filter-dropdown-menu filter-drop-items">
										<form action="{{url('fees-assign')}}">
											<div class="filter-head-title">
												<h4>Select Fees Type</h4>
											</div>
											<div class="form-wrap icon-form">
												<span class="form-icon"><i class="ti ti-search"></i></span>
												<input type="text" class="form-control" placeholder="Search">
											</div>
											<div class="access-wrap">
												<ul>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox" checked>
															<span class="checkmarks"></span>
															<span class="name-id">
																Tuition Fees
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Monthly Fees
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Admission Fees
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Bus Fees
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Monthly Fees
															</span>
														</label>
													</li>
												</ul>
											</div>
											<div class="d-flex drop-filter-btn">
												<a href="#" class="btn btn-outline-primary flex-fill"
													data-bs-dismiss="modal">Reset</a>
												<button type="submit"
													class="btn btn-primary flex-fill ms-3">Apply</button>
											</div>
										</form>
									</div>
								</div>
								<div class="form-filter dropdown">
									<a href="javascript:void(0);" class="dropdown-toggle"><i
											class="ti ti-calendar-due me-2"></i>Class</a>
									<div class="filter-dropdown-menu filter-drop-items">
										<form action="{{url('fees-assign')}}">
											<div class="filter-head-title">
												<h4>Select Class</h4>
											</div>
											<div class="form-wrap icon-form">
												<span class="form-icon"><i class="ti ti-search"></i></span>
												<input type="text" class="form-control" placeholder="Search">
											</div>
											<div class="access-wrap">
												<ul>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox" checked>
															<span class="checkmarks"></span>
															<span class="name-id">
																I
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																II
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																III
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																IV
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																V
															</span>
														</label>
													</li>
												</ul>
											</div>
											<div class="d-flex drop-filter-btn">
												<a href="#" class="btn btn-outline-primary flex-fill"
													data-bs-dismiss="modal">Reset</a>
												<button type="submit"
													class="btn btn-primary flex-fill ms-3">Apply</button>
											</div>
										</form>
									</div>
								</div>
								<div class="form-filter dropdown">
									<a href="javascript:void(0);" class="dropdown-toggle"><i
											class="ti ti-calendar-due me-2"></i>Section</a>
									<div class="filter-dropdown-menu filter-drop-items">
										<form action="{{url('fees-assign')}}">
											<div class="filter-head-title">
												<h4>Select Section</h4>
											</div>
											<div class="form-wrap icon-form">
												<span class="form-icon"><i class="ti ti-search"></i></span>
												<input type="text" class="form-control" placeholder="Search">
											</div>
											<div class="access-wrap">
												<ul>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox" checked>
															<span class="checkmarks"></span>
															<span class="name-id">
																A
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																B
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																C
															</span>
														</label>
													</li>
												</ul>
											</div>
											<div class="d-flex drop-filter-btn">
												<a href="#" class="btn btn-outline-primary flex-fill"
													data-bs-dismiss="modal">Reset</a>
												<button type="submit"
													class="btn btn-primary flex-fill ms-3">Apply</button>
											</div>
										</form>
									</div>
								</div>
								<div class="form-filter dropdown">
									<a href="javascript:void(0);" class="dropdown-toggle"><i
											class="ti ti-calendar-due me-2"></i>Gender</a>
									<div class="filter-dropdown-menu filter-drop-items">
										<form action="{{url('fees-assign')}}">
											<div class="filter-head-title">
												<h4>Select Fine Type</h4>
											</div>
											<div class="form-wrap icon-form">
												<span class="form-icon"><i class="ti ti-search"></i></span>
												<input type="text" class="form-control" placeholder="Search">
											</div>
											<div class="access-wrap">
												<ul>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox" checked>
															<span class="checkmarks"></span>
															<span class="name-id">
																Male
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Female
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Both
															</span>
														</label>
													</li>
												</ul>
											</div>
											<div class="d-flex drop-filter-btn">
												<a href="#" class="btn btn-outline-primary flex-fill"
													data-bs-dismiss="modal">Reset</a>
												<button type="submit"
													class="btn btn-primary flex-fill ms-3">Apply</button>
											</div>
										</form>
									</div>
								</div>
								<div class="form-filter dropdown">
									<a href="javascript:void(0);" class="dropdown-toggle"><i
											class="ti ti-calendar-due me-2"></i>Student Category</a>
									<div class="filter-dropdown-menu filter-drop-items">
										<form action="{{url('fees-assign')}}">
											<div class="filter-head-title">
												<h4>Select Student Category</h4>
											</div>
											<div class="form-wrap icon-form">
												<span class="form-icon"><i class="ti ti-search"></i></span>
												<input type="text" class="form-control" placeholder="Search">
											</div>
											<div class="access-wrap">
												<ul>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox" checked>
															<span class="checkmarks"></span>
															<span class="name-id">
																BC
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																MBC
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																FC
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																BC
															</span>
														</label>
													</li>
												</ul>
											</div>
											<div class="d-flex drop-filter-btn">
												<a href="#" class="btn btn-outline-primary flex-fill"
													data-bs-dismiss="modal">Reset</a>
												<button type="submit"
													class="btn btn-primary flex-fill ms-3">Apply</button>
											</div>
										</form>
									</div>
								</div>
							</div>
							<!-- /Table Filter -->
						</div>
						<!-- /Filter Section -->
						<div class="modal-card-table">
							<div class="modal-table-head">
								<h4>List of Fees type</h4>
							</div>
							<div class="table-responsive custom-table no-datatable_length">
								<table class="table datanew">
									<thead class="thead-light">
										<tr>
											<th class="no-sort">

											</th>
											<th class="no-sort">Fees Type</th>
											<th class="no-sort">Fees Type</th>
											<th class="no-sort">Amount</th>
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
											<td>Admission Fees</td>
											<td>Admission-Fees</td>
											<td>5000</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td>Apr-Mar</td>
											<td>Apr-Mar</td>
											<td>666</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td>Bus Fees</td>
											<td>Bus Fees</td>
											<td>400</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td>1st Installment Fees</td>
											<td>1st Installment Fees</td>
											<td>2645</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td>2nd Installment Fees</td>
											<td>2nd Installment Fees</td>
											<td>7898</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td>3rd Installment Fees</td>
											<td>3rd Installment Fees</td>
											<td>4785</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
						<div class="modal-card-table">
							<div class="modal-table-head">
								<h4>Student Details</h4>
							</div>
							<div class="table-responsive custom-table no-datatable_length">
								<table class="table datanew">
									<thead class="thead-light">
										<tr>
											<th class="no-sort">
												<label class="checkboxs"><input type="checkbox" id="select-all2"><span
														class="checkmarks"></span></label>
											</th>
											<th class="no-sort">Admission Number</th>
											<th class="no-sort">Student</th>
											<th class="no-sort">Class</th>
											<th class="no-sort">Section</th>
											<th class="no-sort">Gender</th>
											<th class="no-sort">Student Category</th>
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
											<td><a href="#" class="text-primary">AD9892430</a></td>
											<td>
												<div class="d-flex align-items-center">
													<a href="{{url('student-details')}}" class="avatar avatar-md"><img
															src="{{URL::asset('build/img/students/student-01.jpg')}}"
															class="img-fluid rounded-circle" alt="img"></a>
													<div class="ms-2">
														<p class="text-dark mb-0"><a
																href="{{url('student-details')}}">Janet</a></p>
													</div>
												</div>
											</td>
											<td>II</td>
											<td>B</td>
											<td>Female</td>
											<td>MBC</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td><a href="#" class="text-primary">AD9892429</a></td>
											<td>
												<div class="d-flex align-items-center">
													<a href="{{url('student-details')}}" class="avatar avatar-md"><img
															src="{{URL::asset('build/img/students/student-06.jpg')}}"
															class="img-fluid rounded-circle" alt="img"></a>
													<div class="ms-2">
														<p class="text-dark mb-0"><a
																href="{{url('student-details')}}">Ralph</a></p>
													</div>
												</div>
											</td>
											<td>III</td>
											<td>B</td>
											<td>Male</td>
											<td>BC</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td><a href="#" class="text-primary">AD9892428</a></td>
											<td>
												<div class="d-flex align-items-center">
													<a href="{{url('student-details')}}" class="avatar avatar-md"><img
															src="{{URL::asset('build/img/students/student-07.jpg')}}"
															class="img-fluid rounded-circle" alt="img"></a>
													<div class="ms-2">
														<p class="text-dark mb-0"><a
																href="{{url('student-details')}}">Julie</a></p>
													</div>
												</div>
											</td>
											<td>V</td>
											<td>A</td>
											<td>Female</td>
											<td>BC</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td><a href="#" class="text-primary">AD9892427</a></td>
											<td>
												<div class="d-flex align-items-center">
													<a href="{{url('student-details')}}" class="avatar avatar-md"><img
															src="{{URL::asset('build/img/students/student-08.jpg')}}"
															class="img-fluid rounded-circle" alt="img"></a>
													<div class="ms-2">
														<p class="text-dark mb-0"><a
																href="{{url('student-details')}}">Ryan</a></p>
													</div>
												</div>
											</td>
											<td>VI</td>
											<td>A</td>
											<td>Male</td>
											<td>MBC</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td><a href="#" class="text-primary">AD9892426</a></td>
											<td>
												<div class="d-flex align-items-center">
													<a href="{{url('student-details')}}" class="avatar avatar-md"><img
															src="{{URL::asset('build/img/students/student-09.jpg')}}"
															class="img-fluid rounded-circle" alt="img"></a>
													<div class="ms-2">
														<p class="text-dark mb-0"><a
																href="{{url('student-details')}}">Susan</a></p>
													</div>
												</div>
											</td>
											<td>VIII</td>
											<td>B</td>
											<td>Female</td>
											<td>BC</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td><a href="#" class="text-primary">AD9892425</a></td>
											<td>
												<div class="d-flex align-items-center">
													<a href="{{url('student-details')}}" class="avatar avatar-md"><img
															src="{{URL::asset('build/img/students/student-12.jpg')}}"
															class="img-fluid rounded-circle" alt="img"></a>
													<div class="ms-2">
														<p class="text-dark mb-0"><a
																href="{{url('student-details')}}">Richard</a></p>
													</div>
												</div>
											</td>
											<td>VII</td>
											<td>B</td>
											<td>Male</td>
											<td>MBC</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td><a href="#" class="text-primary">AD9892424</a></td>
											<td>
												<div class="d-flex align-items-center">
													<a href="{{url('student-details')}}" class="avatar avatar-md"><img
															src="{{URL::asset('build/img/students/student-11.jpg')}}"
															class="img-fluid rounded-circle" alt="img"></a>
													<div class="ms-2">
														<p class="text-dark mb-0"><a
																href="{{url('student-details')}}">Veronica</a></p>
													</div>
												</div>
											</td>
											<td>IX</td>
											<td>A</td>
											<td>Female</td>
											<td>BC</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
						<div class="student-pomote-note d-flex mb-0">
							<span class="info-icon"><i class="ti ti-info-circle"></i></span>
							<p> Selected 50 Fees Group, 350 Students</p>
						</div>
					</div>
					<div class="modal-footer">
						<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
						<a href="{{url('fees-assign')}}" class="btn btn-primary">Add Fees</a>
					</div>
				</div>
			</div>
		</div>
		<!-- Add Fees Assign -->

		<!-- Edit Fees Assign -->
		<div class="modal fade" id="edit_fees_assign">
			<div class="modal-dialog modal-dialog-centered  modal-xl">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Fees</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<div class="modal-body">
						<!-- Table Filter -->
						<div class="table-filter-head">
							<div class="filter-head-left">
								<h5>Search Criteria</h5>
								<div class="form-filter dropdown">
									<a href="javascript:void(0);" class="dropdown-toggle"><i
											class="ti ti-calendar-due me-2"></i>Fees Group</a>
									<div class="filter-dropdown-menu filter-drop-items">
										<form action="{{url('fees-assign')}}">
											<div class="filter-head-title">
												<h4>Select Fees Group</h4>
											</div>
											<div class="form-wrap icon-form">
												<span class="form-icon"><i class="ti ti-search"></i></span>
												<input type="text" class="form-control" placeholder="Search">
											</div>
											<div class="access-wrap">
												<ul>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox" checked>
															<span class="checkmarks"></span>
															<span class="name-id">
																Admission-Fees
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Class 1 General
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Monthly Fees
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox" checked>
															<span class="checkmarks"></span>
															<span class="name-id">
																Class 1 Lump Sum
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Class 1- I Installment
															</span>
														</label>
													</li>
												</ul>
											</div>
											<div class="d-flex drop-filter-btn">
												<a href="#" class="btn btn-outline-primary flex-fill"
													data-bs-dismiss="modal">Reset</a>
												<button type="submit"
													class="btn btn-primary flex-fill ms-3">Apply</button>
											</div>
										</form>
									</div>
								</div>
								<div class="form-filter dropdown">
									<a href="javascript:void(0);" class="dropdown-toggle"><i
											class="ti ti-calendar-due me-2"></i>Fees Type</a>
									<div class="filter-dropdown-menu filter-drop-items">
										<form action="{{url('fees-assign')}}">
											<div class="filter-head-title">
												<h4>Select Fees Type</h4>
											</div>
											<div class="form-wrap icon-form">
												<span class="form-icon"><i class="ti ti-search"></i></span>
												<input type="text" class="form-control" placeholder="Search">
											</div>
											<div class="access-wrap">
												<ul>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox" checked>
															<span class="checkmarks"></span>
															<span class="name-id">
																Tuition Fees
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Monthly Fees
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Admission Fees
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Bus Fees
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Monthly Fees
															</span>
														</label>
													</li>
												</ul>
											</div>
											<div class="d-flex drop-filter-btn">
												<a href="#" class="btn btn-outline-primary flex-fill"
													data-bs-dismiss="modal">Reset</a>
												<button type="submit"
													class="btn btn-primary flex-fill ms-3">Apply</button>
											</div>
										</form>
									</div>
								</div>
								<div class="form-filter dropdown">
									<a href="javascript:void(0);" class="dropdown-toggle"><i
											class="ti ti-calendar-due me-2"></i>Class</a>
									<div class="filter-dropdown-menu filter-drop-items">
										<form action="{{url('fees-assign')}}">
											<div class="filter-head-title">
												<h4>Select Class</h4>
											</div>
											<div class="form-wrap icon-form">
												<span class="form-icon"><i class="ti ti-search"></i></span>
												<input type="text" class="form-control" placeholder="Search">
											</div>
											<div class="access-wrap">
												<ul>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox" checked>
															<span class="checkmarks"></span>
															<span class="name-id">
																I
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																II
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																III
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																IV
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																V
															</span>
														</label>
													</li>
												</ul>
											</div>
											<div class="d-flex drop-filter-btn">
												<a href="#" class="btn btn-outline-primary flex-fill"
													data-bs-dismiss="modal">Reset</a>
												<button type="submit"
													class="btn btn-primary flex-fill ms-3">Apply</button>
											</div>
										</form>
									</div>
								</div>
								<div class="form-filter dropdown">
									<a href="javascript:void(0);" class="dropdown-toggle"><i
											class="ti ti-calendar-due me-2"></i>Section</a>
									<div class="filter-dropdown-menu filter-drop-items">
										<form action="{{url('fees-assign')}}">
											<div class="filter-head-title">
												<h4>Select Section</h4>
											</div>
											<div class="form-wrap icon-form">
												<span class="form-icon"><i class="ti ti-search"></i></span>
												<input type="text" class="form-control" placeholder="Search">
											</div>
											<div class="access-wrap">
												<ul>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox" checked>
															<span class="checkmarks"></span>
															<span class="name-id">
																A
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																B
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																C
															</span>
														</label>
													</li>
												</ul>
											</div>
											<div class="d-flex drop-filter-btn">
												<a href="#" class="btn btn-outline-primary flex-fill"
													data-bs-dismiss="modal">Reset</a>
												<button type="submit"
													class="btn btn-primary flex-fill ms-3">Apply</button>
											</div>
										</form>
									</div>
								</div>
								<div class="form-filter dropdown">
									<a href="javascript:void(0);" class="dropdown-toggle"><i
											class="ti ti-calendar-due me-2"></i>Gender</a>
									<div class="filter-dropdown-menu filter-drop-items">
										<form action="{{url('fees-assign')}}">
											<div class="filter-head-title">
												<h4>Select Fine Type</h4>
											</div>
											<div class="form-wrap icon-form">
												<span class="form-icon"><i class="ti ti-search"></i></span>
												<input type="text" class="form-control" placeholder="Search">
											</div>
											<div class="access-wrap">
												<ul>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox" checked>
															<span class="checkmarks"></span>
															<span class="name-id">
																Male
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Female
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																Both
															</span>
														</label>
													</li>
												</ul>
											</div>
											<div class="d-flex drop-filter-btn">
												<a href="#" class="btn btn-outline-primary flex-fill"
													data-bs-dismiss="modal">Reset</a>
												<button type="submit"
													class="btn btn-primary flex-fill ms-3">Apply</button>
											</div>
										</form>
									</div>
								</div>
								<div class="form-filter dropdown">
									<a href="javascript:void(0);" class="dropdown-toggle"><i
											class="ti ti-calendar-due me-2"></i>Student Category</a>
									<div class="filter-dropdown-menu filter-drop-items">
										<form action="{{url('fees-assign')}}">
											<div class="filter-head-title">
												<h4>Select Student Category</h4>
											</div>
											<div class="form-wrap icon-form">
												<span class="form-icon"><i class="ti ti-search"></i></span>
												<input type="text" class="form-control" placeholder="Search">
											</div>
											<div class="access-wrap">
												<ul>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox" checked>
															<span class="checkmarks"></span>
															<span class="name-id">
																BC
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																MBC
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																FC
															</span>
														</label>
													</li>
													<li class="select-people-checkbox">
														<label class="checkboxs">
															<input type="checkbox">
															<span class="checkmarks"></span>
															<span class="name-id">
																BC
															</span>
														</label>
													</li>
												</ul>
											</div>
											<div class="d-flex drop-filter-btn">
												<a href="#" class="btn btn-outline-primary flex-fill"
													data-bs-dismiss="modal">Reset</a>
												<button type="submit"
													class="btn btn-primary flex-fill ms-3">Apply</button>
											</div>
										</form>
									</div>
								</div>
							</div>
							<!-- /Table Filter -->
						</div>
						<!-- /Filter Section -->
						<div class="modal-card-table">
							<div class="modal-table-head">
								<h4>List of Fees type</h4>
							</div>
							<div class="table-responsive custom-table no-datatable_length">
								<table class="table datanew">
									<thead class="thead-light">
										<tr>
											<th class="no-sort">

											</th>
											<th class="no-sort">Fees Type</th>
											<th class="no-sort">Fees Type</th>
											<th class="no-sort">Amount</th>
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
											<td>Admission Fees</td>
											<td>Admission-Fees</td>
											<td>5000</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td>Apr-Mar</td>
											<td>Apr-Mar</td>
											<td>666</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td>Bus Fees</td>
											<td>Bus Fees</td>
											<td>400</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td>1st Installment Fees</td>
											<td>1st Installment Fees</td>
											<td>2645</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td>2nd Installment Fees</td>
											<td>2nd Installment Fees</td>
											<td>7898</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td>3rd Installment Fees</td>
											<td>3rd Installment Fees</td>
											<td>4785</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
						<div class="modal-card-table">
							<div class="modal-table-head">
								<h4>Student Details</h4>
							</div>
							<div class="table-responsive custom-table no-datatable_length">
								<table class="table datanew">
									<thead class="thead-light">
										<tr>
											<th class="no-sort">
												<label class="checkboxs"><input type="checkbox" id="select-all3"><span
														class="checkmarks"></span></label>
											</th>
											<th class="no-sort">Admission Number</th>
											<th class="no-sort">Student</th>
											<th class="no-sort">Class</th>
											<th class="no-sort">Section</th>
											<th class="no-sort">Gender</th>
											<th class="no-sort">Student Category</th>
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
											<td><a href="#" class="text-primary">AD9892430</a></td>
											<td>
												<div class="d-flex align-items-center">
													<a href="{{url('student-details')}}" class="avatar avatar-md"><img
															src="{{URL::asset('build/img/students/student-01.jpg')}}"
															class="img-fluid rounded-circle" alt="img"></a>
													<div class="ms-2">
														<p class="text-dark mb-0"><a
																href="{{url('student-details')}}">Janet</a></p>
													</div>
												</div>
											</td>
											<td>II</td>
											<td>B</td>
											<td>Female</td>
											<td>MBC</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td><a href="#" class="text-primary">AD9892429</a></td>
											<td>
												<div class="d-flex align-items-center">
													<a href="{{url('student-details')}}" class="avatar avatar-md"><img
															src="{{URL::asset('build/img/students/student-06.jpg')}}"
															class="img-fluid rounded-circle" alt="img"></a>
													<div class="ms-2">
														<p class="text-dark mb-0"><a
																href="{{url('student-details')}}">Ralph</a></p>
													</div>
												</div>
											</td>
											<td>III</td>
											<td>B</td>
											<td>Male</td>
											<td>BC</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td><a href="#" class="text-primary">AD9892428</a></td>
											<td>
												<div class="d-flex align-items-center">
													<a href="{{url('student-details')}}" class="avatar avatar-md"><img
															src="{{URL::asset('build/img/students/student-07.jpg')}}"
															class="img-fluid rounded-circle" alt="img"></a>
													<div class="ms-2">
														<p class="text-dark mb-0"><a
																href="{{url('student-details')}}">Julie</a></p>
													</div>
												</div>
											</td>
											<td>V</td>
											<td>A</td>
											<td>Female</td>
											<td>BC</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td><a href="#" class="text-primary">AD9892427</a></td>
											<td>
												<div class="d-flex align-items-center">
													<a href="{{url('student-details')}}" class="avatar avatar-md"><img
															src="{{URL::asset('build/img/students/student-08.jpg')}}"
															class="img-fluid rounded-circle" alt="img"></a>
													<div class="ms-2">
														<p class="text-dark mb-0"><a
																href="{{url('student-details')}}">Ryan</a></p>
													</div>
												</div>
											</td>
											<td>VI</td>
											<td>A</td>
											<td>Male</td>
											<td>MBC</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td><a href="#" class="text-primary">AD9892426</a></td>
											<td>
												<div class="d-flex align-items-center">
													<a href="{{url('student-details')}}" class="avatar avatar-md"><img
															src="{{URL::asset('build/img/students/student-09.jpg')}}"
															class="img-fluid rounded-circle" alt="img"></a>
													<div class="ms-2">
														<p class="text-dark mb-0"><a
																href="{{url('student-details')}}">Susan</a></p>
													</div>
												</div>
											</td>
											<td>VIII</td>
											<td>B</td>
											<td>Female</td>
											<td>BC</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td><a href="#" class="text-primary">AD9892425</a></td>
											<td>
												<div class="d-flex align-items-center">
													<a href="{{url('student-details')}}" class="avatar avatar-md"><img
															src="{{URL::asset('build/img/students/student-12.jpg')}}"
															class="img-fluid rounded-circle" alt="img"></a>
													<div class="ms-2">
														<p class="text-dark mb-0"><a
																href="{{url('student-details')}}">Richard</a></p>
													</div>
												</div>
											</td>
											<td>VII</td>
											<td>B</td>
											<td>Male</td>
											<td>MBC</td>
										</tr>
										<tr>
											<td>
												<label class="checkboxs">
													<input type="checkbox">
													<span class="checkmarks"></span>
												</label>
											</td>
											<td><a href="#" class="text-primary">AD9892424</a></td>
											<td>
												<div class="d-flex align-items-center">
													<a href="{{url('student-details')}}" class="avatar avatar-md"><img
															src="{{URL::asset('build/img/students/student-11.jpg')}}"
															class="img-fluid rounded-circle" alt="img"></a>
													<div class="ms-2">
														<p class="text-dark mb-0"><a
																href="{{url('student-details')}}">Veronica</a></p>
													</div>
												</div>
											</td>
											<td>IX</td>
											<td>A</td>
											<td>Female</td>
											<td>BC</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
						<div class="student-pomote-note d-flex mb-0">
							<span class="info-icon"><i class="ti ti-info-circle"></i></span>
							<p> Selected 50 Fees Group, 350 Students</p>
						</div>
					</div>
					<div class="modal-footer">
						<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
						<a href="{{url('fees-assign')}}" class="btn btn-primary">Save Changes</a>
					</div>
				</div>
			</div>
		</div>
		<!-- Edit Fees Assign -->


@endif
@if(Route::is(['student-promotion']))
<div class="modal fade" id="student_promote">
    <div class="modal-dialog modal-dialog-centered custom-modal promote-modal">
        <div class="modal-content">
            <div class="modal-wrapper">					
                <div class="promote-modal-body text-center">
                    <h4>Confirm Promotion</h4>
                    <p>Are you Sure, want to promote all 57 selected students to the next academic session</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                        <a href="#" class="btn btn-danger" id="toprightToastBtn" data-bs-dismiss="modal">Promote</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endif

@if(Route::is(['collect-fees']))
  <!-- Add Fees Collect -->
  <div class="modal fade" id="add_fees_collect">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <h4 class="modal-title">Collect Fees</h4>
                    <span class="badge badge-sm bg-primary ms-2">AD124556</span>
                </div>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('collect-fees')}}">
                <div class="modal-body">
                    <div class="bg-light-300 p-3 pb-0 rounded mb-4">
                        <div class="row align-items-center">
                            <div class="col-lg-3 col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <a href="{{url('student-details')}}" class="avatar avatar-md me-2">
                                        <img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="img">
                                    </a>
                                    <a href="{{url('student-details')}}" class="d-flex flex-column"><span class="text-dark">Janet</span>III, A</a>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="fs-12 mb-1">Total Outstanding</span>
                                    <p class="text-dark">2000</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="fs-12 mb-1">Last Date</span>
                                    <p class="text-dark">25 May 2024</p>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="mb-3">
                                    <span class="badge badge-soft-danger"><i
                                    class="ti ti-circle-filled me-2"></i>Unpaid</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Fees Group</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Class 1 General</option>
                                    <option>Monthly Fees</option>
                                    <option>Admission-Fees</option>
                                    <option>Class 1- I Installment</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Fees Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Tuition Fees</option>
                                    <option>Monthly Fees</option>
                                    <option>Admission Fees</option>
                                    <option>Bus Fees</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="text" class="form-control" placeholder="Enter Amout">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Collection Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="Select">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Paytm</option>
                                    <option>Cash On Delivery</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Reference No</label>
                                <input type="text" class="form-control"
                                    placeholder="Enter Payment Reference No">
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="switch-sm">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-0">
                                <label class="form-label">Notes</label>
                                <textarea rows="4" class="form-control" placeholder="Add Notes"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Pay Fees</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Fees Collect -->
									
@endif

@if (Route::is(['library-members']))
  <!-- Add Member -->
  <div class="modal fade" id="add_library_members">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Member</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('library-members')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" placeholder="Enter Name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Card No</label>
                                <input type="text" class="form-control" placeholder="Enter Card No">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control" placeholder="Enter Email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date of Join</label>
                                <input type="text" class="form-control" placeholder="Enter Date of Join">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" placeholder="Enter Phone Number">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Member -->

<!-- Edit Member -->
<div class="modal fade" id="edit_library_members">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Member</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('library-members')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" placeholder="Enter Name"
                                    value="James">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Card No</label>
                                <input type="text" class="form-control" placeholder="Enter Card No"
                                    value="501">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control" placeholder="Enter Email"
                                    value="james@example.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date of Join</label>
                                <input type="text" class="form-control" placeholder="Enter Date of Join"
                                    value="22 Apr 2024">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" placeholder="Enter Phone Number"
                                    value="+1 78429 82414">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Member -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('library-members')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->

@endif

@if (Route::is(['library-books']))
	<!-- Add Book -->
    <div class="modal fade" id="add_library_book">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Book</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('library-books')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Book Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Book Name">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Book No</label>
                                            <input type="text" class="form-control" placeholder="Enter Book No">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Rack No</label>
                                            <input type="text" class="form-control"
                                                placeholder="Enter Rack Name">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Publisher</label>
                                    <input type="text" class="form-control" placeholder="Enter Publisher">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Author</label>
                                    <input type="text" class="form-control" placeholder="Enter Author">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Subject</label>
                                    <input type="text" class="form-control" placeholder="Enter Subject">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Qty</label>
                                            <input type="text" class="form-control" placeholder="Enter Qty">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Available</label>
                                            <input type="text" class="form-control"
                                                placeholder="Enter Available">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="text" class="form-control" placeholder="Enter Price">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Post Date</label>
                                    <input type="text" class="form-control" placeholder="Enter Price">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add Book -->

    <!-- Edit Book -->
    <div class="modal fade" id="edit_library_book">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Book</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('library-books')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Book Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Book Name"
                                        value="Echoes of Eternity">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Book No</label>
                                            <input type="text" class="form-control" placeholder="Enter Book No"
                                                value="501">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Rack No</label>
                                            <input type="text" class="form-control"
                                                placeholder="Enter Rack Name" value="6550">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Publisher</label>
                                    <input type="text" class="form-control" placeholder="Enter Publisher"
                                        value="Aurora Press">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Author</label>
                                    <input type="text" class="form-control" placeholder="Enter Author"
                                        value=" Isabella Rivers">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Subject</label>
                                    <input type="text" class="form-control" placeholder="Enter Subject"
                                        value="History">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Qty</label>
                                            <input type="text" class="form-control" placeholder="Enter Qty"
                                                value="150">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Available</label>
                                            <input type="text" class="form-control"
                                                placeholder="Enter Available" value="120">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="text" class="form-control" placeholder="Enter Price"
                                        value="$300">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Post Date</label>
                                    <input type="text" class="form-control" placeholder="Enter Post Date"
                                        value="25 Apr 2024">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Edit Book -->

    <!-- Delete Modal -->
    <div class="modal fade" id="delete-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{url('library-books')}}">
                    <div class="modal-body text-center">
                        <span class="delete-icon">
                            <i class="ti ti-trash-x"></i>
                        </span>
                        <h4>Confirm Deletion</h4>
                        <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                        <div class="d-flex justify-content-center">
                            <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                            <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Delete Modal -->


   @endif

@if (Route::is(['sports']))
	<!-- Add Sports -->
    <div class="modal fade" id="add_sports">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Sport</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('sports')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Coach</label>
                                    <input type="text" class="form-control">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Started Year</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Sport</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add Sports -->

    <!-- Edit Sports -->
    <div class="modal fade" id="edit_sports">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Sport</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('sports')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Name"
                                        value="Cricket">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Coach</label>
                                    <input type="text" class="form-control" placeholder="Enter Coach"
                                        value="Thomas">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Started Year</label>
                                    <input type="text" class="form-control" placeholder="Enter Started Year"
                                        value="2004">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Edit Sports -->

    <!-- Delete Modal -->
    <div class="modal fade" id="delete-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{url('sports')}}">
                    <div class="modal-body text-center">
                        <span class="delete-icon">
                            <i class="ti ti-trash-x"></i>
                        </span>
                        <h4>Confirm Deletion</h4>
                        <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                        <div class="d-flex justify-content-center">
                            <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                            <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Delete Modal -->


   @endif

@if (Route::is(['hostel-list']))
   <!-- Add Hostel -->
		<div class="modal fade" id="add_hostel">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Hostel</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('hostel-list')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Hostel Name</label>
										<input type="text" class="form-control" placeholder="Enter Hostel Name">
									</div>
									<div class="mb-3">
										<label class="form-label">Hostel Type</label>
										<select class="select">
											<option>Select</option>
											<option>Boys</option>
											<option>Girls</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Intake</label>
										<input type="text" class="form-control" placeholder="Enter Intake">
									</div>
									<div class="mb-3">
										<label class="form-label">Address</label>
										<input type="text" class="form-control" placeholder="Enter Address">
									</div>
									<div class="mb-0">
										<label class="form-label">Description</label>
										<textarea placeholder="text" class="form-control" rows="4"></textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Hostel</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Add Hostel -->

		<!-- Edit Hostel -->
		<div class="modal fade" id="edit_hostel">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Hostel</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('hostel-list')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Hostel Name</label>
										<input type="text" class="form-control" placeholder="Enter Hostel Name">
									</div>
									<div class="mb-4">
										<label class="form-label">Hostel Type</label>
										<select class="select">
											<option>Select</option>
											<option selected>Boys</option>
											<option>Girls</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Intake</label>
										<input type="text" class="form-control" placeholder="Enter Intake"
											value="150">
									</div>
									<div class="mb-3">
										<label class="form-label">Address</label>
										<input type="text" class="form-control" placeholder="Enter Address"
											value="25 Crowfield Road, Phoenix	1">
									</div>
									<div class="mb-0">
										<label class="form-label">Description</label>
										<textarea placeholder="text" class="form-control"
											rows="4">Rising to nurture young minds</textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Edit Hostel -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('hostel-list')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->
@endif

@if (Route::is(['hostel-rooms']))
  <!-- Add Hostel Rooms -->
  <div class="modal fade" id="add_hostel_rooms">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Hostel Room</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('hostel-rooms')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Room No</label>
                                <input type="text" class="form-control" placeholder="Enter Room No">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hostel Name</label>
                                <input type="text" class="form-control" placeholder="Enter Hostel Name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Room Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>One Bed</option>
                                    <option>One Bed AC</option>
                                    <option>Two Bed</option>
                                    <option>Two Bed AC</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">No of Bed</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>1</option>
                                    <option>2</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Cost per Bed</label>
                                <input type="text" class="form-control" placeholder="Enter Cost per Bed">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Hostel Room</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Hostel Rooms -->

<!-- Edit Hostel Room -->
<div class="modal fade" id="edit_hostel_rooms">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Hostel Room</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('hostel-rooms')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Room No</label>
                                <input type="text" class="form-control" placeholder="Enter Room No"
                                    value="A1">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hostel Name</label>
                                <input type="text" class="form-control" placeholder="Enter Hostel Name"
                                    value="Phoenix Residence">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Room Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option selected>One Bed</option>
                                    <option>One Bed AC</option>
                                    <option>Two Bed</option>
                                    <option>Two Bed AC</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">No of Bed</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option selected>1</option>
                                    <option>2</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Cost per Bed</label>
                                <input type="text" class="form-control" placeholder="Enter Cost per Bed"
                                    value="$200">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Hostel Room -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('hostel-rooms')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->
@endif

@if(Route::is(['hostel-room-type']))
   <!-- Add Room Type-->
		<div class="modal fade" id="add_hostel_room_type">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Room Type</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('hostel-room-type')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Room Type</label>
										<input type="text" class="form-control" placeholder="Enter Room Type">
									</div>
									<div class="mb-0">
										<label class="form-label">Cost per Bed</label>
										<textarea class="form-control" placeholder="text" rows="4"></textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Room Type</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Add Room Type -->

		<!-- Edit Room Type -->
		<div class="modal fade" id="edit_hostel_room_type">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Room Type</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('hostel-room-type')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Room Type</label>
										<input type="text" class="form-control" placeholder="Enter Room Type"
											value="Two Bed">
									</div>
									<div class="mb-0">
										<label class="form-label">Cost per Bed</label>
										<textarea class="form-control" placeholder="text"
											rows="4">Enjoy serene solitude in our one-bed room, your tranquil retreat for focused studying</textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Edit Room Type -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('hostel-room-type')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->
@endif

@if (Route::is(['transport-routes']))
   <!-- Add Route -->
		<div class="modal fade" id="add_routes">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Route</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('transport-routes')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Route Name</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
									<div class="status-title">
										<h5>Status</h5>
										<p>Change the Status by toggle </p>
									</div>
									<div class="status-toggle modal-status">
										<input type="checkbox" id="user1" class="check">
										<label for="user1" class="checktoggle"> </label>
									</div>
								</div>
							</div>
						</div>

						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Route</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Add Route-->

		<!-- Edit Route -->
		<div class="modal fade" id="edit_routes">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Route</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('transport-routes')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Route Name</label>
										<input type="text" class="form-control" placeholder="Enter Route Name"
											value="Seattle">
									</div>
								</div>
								<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
									<div class="status-title">
										<h5>Status</h5>
										<p>Change the Status by toggle </p>
									</div>
									<div class="status-toggle modal-status">
										<input type="checkbox" id="user2" class="check" checked>
										<label for="user2" class="checktoggle"> </label>
									</div>
								</div>
							</div>
						</div>

						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Edit Route -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('transport-routes')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3"
									data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

@endif

@if (Route::is(['transport-pickup-points']))
<!-- Add Pickup -->
<div class="modal fade" id="add_pickup">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Pickup Point</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('transport-pickup-points')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Pickup Point</label>
                                <input type="text" class="form-control" placeholder="Enter Pickup Point">
                            </div>
                        </div>
                        <div class="modal-satus-toggle d-flex align-items-center justify-content-between">
                            <div class="status-title">
                                <h5>Status</h5>
                                <p>Change the Status by toggle </p>
                            </div>
                            <div class="status-toggle modal-status">
                                <input type="checkbox" id="user1" class="check">
                                <label for="user1" class="checktoggle"> </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Pickup Point</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Pickup -->

<!-- Edit Pickup -->
<div class="modal fade" id="edit_pickup">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Pickup Point</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('transport-pickup-points')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Pickup Point</label>
                                <input type="text" class="form-control" placeholder="Enter Pickup Point"
                                    value="2603 Wood Duck Drive Marquette, MI">
                            </div>
                        </div>
                        <div class="modal-satus-toggle d-flex align-items-center justify-content-between">
                            <div class="status-title">
                                <h5>Status</h5>
                                <p>Change the Status by toggle </p>
                            </div>
                            <div class="status-toggle modal-status">
                                <input type="checkbox" id="user2" class="check" checked>
                                <label for="user2" class="checktoggle"> </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Pickup -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('transport-pickup-points')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->

   @endif

@if (Route::is(['transport-vehicle-drivers']))
	<!-- Add Driver -->
    <div class="modal fade" id="add_driver">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add New Driver</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('transport-vehicle-drivers')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" >
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Driving License Number</label>
                                    <input type="text" class="form-control"
                                       >
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" >
                                </div>
                            </div>
                            <div class="modal-satus-toggle d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="status-toggle modal-status">
                                    <input type="checkbox" id="user1" class="check">
                                    <label for="user1" class="checktoggle"> </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Driver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add Driver -->

    <!-- Edit Driver -->
    <div class="modal fade" id="edit_driver">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Driver</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('transport-vehicle-drivers')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Name" value="Thomas">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" placeholder="Enter Phone Number"
                                        value="+1 64044 74890">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Driving License Number</label>
                                    <input type="text" class="form-control"
                                        placeholder="Enter Driving License Number" value="LC7899456689">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" placeholder="Enter Address"
                                        value="2233 Wood Street, Slidell, LA">
                                </div>
                            </div>
                            <div class="modal-satus-toggle d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="status-toggle modal-status">
                                    <input type="checkbox" id="user2" class="check" checked>
                                    <label for="user2" class="checktoggle"> </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Edit Driver -->

    <!-- Delete Modal -->
    <div class="modal fade" id="delete-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{url('transport-vehicle-drivers')}}">
                    <div class="modal-body text-center">
                        <span class="delete-icon">
                            <i class="ti ti-trash-x"></i>
                        </span>
                        <h4>Confirm Deletion</h4>
                        <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                        <div class="d-flex justify-content-center">
                            <a href="javascript:void(0);" class="btn btn-light me-3"
                                data-bs-dismiss="modal">Cancel</a>
                            <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Delete Modal -->

@endif

@if (Route::is(['transport-vehicle']))
  	<!-- Add New Vehicle -->
      <div class="modal fade" id="add_vehicle">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add New Vehicle</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('transport-vehicle')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Vehicle No</label>
                                            <input type="text" class="form-control" >
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Vehicle Model</label>
                                            <input type="text" class="form-control"
                                                >
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Made of Year</label>
                                            <div class="date-pic">
                                                <input type="text" class="form-control datetimepicker"
                                                   >
                                                <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Registration No</label>
                                            <input type="text" class="form-control"
                                                >
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Chassis No</label>
                                            <input type="text" class="form-control" >
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Seat Capacity</label>
                                            <input type="text" class="form-control"
                                               >
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">GPS Tracking ID</label>
                                    <input type="text" class="form-control" >
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <h4>Driver details</h4>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Select Driver</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Thomas</option>
                                        <option>Mary</option>
                                        <option>Arthur</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Driver License</label>
                                            <input type="text" class="form-control"
                                                >
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Driver Contact No</label>
                                            <input type="text" class="form-control"
                                               >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Driver Address</label>
                                <input type="text" class="form-control" >
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add New Vehicle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add New Vehicle -->

    <!-- Edit New Vehicle -->
    <div class="modal fade" id="edit_vehicle">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header align-items-center">
                    <div class="d-flex align-items-center">
                        <h4 class="modal-title">Edit Vehicle</h4>
                        <span class="badge badge-soft-primary ms-2">ID : BB0482</span>
                    </div>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('transport-vehicle')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Vehicle No</label>
                                            <input type="text" class="form-control" placeholder="Enter Vehicle No"
                                                value="8930">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Vehicle Model</label>
                                            <input type="text" class="form-control"
                                                placeholder="Enter Vehicle Model" value="Scania">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Made of Year</label>
                                            <div class="date-pic">
                                                <input type="text" class="form-control datetimepicker"
                                                    placeholder="Choose">
                                                <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Registration No</label>
                                            <input type="text" class="form-control"
                                                placeholder="Enter Registration No" value="US1A3545">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Chassis No</label>
                                            <input type="text" class="form-control" placeholder="Enter Chassis No"
                                                value="32546665456">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Seat Capacity</label>
                                            <input type="text" class="form-control"
                                                placeholder="Enter Seat Capacity">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">GPS Tracking ID</label>
                                    <input type="text" class="form-control" placeholder="Enter GPS Tracking ID"
                                        value="GPS7899456689">
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <h4>Driver details</h4>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Select Driver</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option selected>Thomas</option>
                                        <option>Mary</option>
                                        <option>Arthur</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Driver License</label>
                                            <input type="text" class="form-control"
                                                placeholder="Enter Driver License" value="LC7899456689">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Driver Contact No</label>
                                            <input type="text" class="form-control"
                                                placeholder="Enter Driver Contact No" value="+1 64044 74890">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Driver Address</label>
                                <input type="text" class="form-control" placeholder="Enter Driver Address"
                                    value="2233 Wood Street, Slidell, LA">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">SAve Vehicle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Edit New Vehicle -->

    <!-- Delete Modal -->
    <div class="modal fade" id="delete-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{url('transport-vehicle')}}">
                    <div class="modal-body text-center">
                        <span class="delete-icon">
                            <i class="ti ti-trash-x"></i>
                        </span>
                        <h4>Confirm Deletion</h4>
                        <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                        <div class="d-flex justify-content-center">
                            <a href="javascript:void(0);" class="btn btn-light me-3"
                                data-bs-dismiss="modal">Cancel</a>
                            <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Delete Modal -->

    <!-- Live Track -->
    <div class="modal fade" id="live_track">
        <div class="modal-dialog modal-dialog-centered  modal-xl">
            <div class="modal-content">
                <div class="modal-header align-items-center">
                    <div class="d-flex align-items-center">
                        <h4 class="modal-title">Live Tracking Vehicle</h4>
                        <span class="badge badge-soft-primary ms-2">GPS Tracking ID : GPS7899456689</span>
                    </div>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body mb-4">
                    <ul class="book-taker-info live-track-info justify-content-between">
                        <li>
                            <span>Vehicle No</span>
                            <h6>8930</h6>
                        </li>
                        <li>
                            <span>Vehicle Model</span>
                            <h6>Scania</h6>
                        </li>
                        <li>
                            <span>Driver</span>
                            <h6>Thomas</h6>
                        </li>
                        <li>
                            <span>Driver Contact No</span>
                            <h6>+1 45644 54784</h6>
                        </li>
                    </ul>
                    <div class="live-track-map w-100">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3321.6088932774796!2d-117.8132203247921!3d33.64138153931407!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80dcddf599c1986f%3A0x6826f6868b4f8e35!2sHillcrest%2C%20Irvine%2C%20CA%2092603%2C%20USA!5e0!3m2!1sen!2sin!4v1706772657955!5m2!1sen!2sin"
                            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <a href="{{url('transport-vehicle')}}" class="btn btn-primary">Reset to Live Location</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Live Track -->
@endif

@if (Route::is(['transport-assign-vehicle']))
<!-- Add Assign New Vehicle -->
<div class="modal fade" id="add_assign_vehicle">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Assign New Vehicle</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('transport-assign-vehicle')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Select Route</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Seattle</option>
                                    <option>Camden</option>
                                    <option>Detroit</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Select Pickup Point</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>2233 Wood Street, Slidell, LA</option>
                                    <option>3167 Stadium Drive, Worcester, MA</option>
                                    <option>4650 Aviation Way, Los Angeles, CA</option>
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Select Vehicle</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>8930</option>
                                    <option>7895</option>
                                    <option>6465</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Assign Now</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Add Assign New Vehicle -->

<!-- Edit Assign New Vehicle -->
<div class="modal fade" id="edit_assign_vehicle">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Assign Vehicle</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('transport-assign-vehicle')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Select Route</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option selected>Seattle</option>
                                    <option>Camden</option>
                                    <option>Detroit</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Select Pickup Point</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option selected>2233 Wood Street, Slidell, LA</option>
                                    <option>3167 Stadium Drive, Worcester, MA</option>
                                    <option>4650 Aviation Way, Los Angeles, CA</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Select Vehicle</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option selected>8930</option>
                                    <option>7895</option>
                                    <option>6465</option>
                                </select>
                            </div>
                            <div class="assigned-driver">
                                <h6>Assigned Driver</h6>
                                <div class="assigned-driver-info">
                                    <span class="driver-img"><img src="{{URL::asset('build/img/parents/parent-01.jpg')}}" alt="Img"></span>
                                    <div>
                                        <h5>Thomas</h5>
                                        <span>+1 64044 748904</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Assign Now</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Assign New Vehicle -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('transport-assign-vehicle')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->


   @endif



@if (Route::is(['departments']))
   <!-- Add Department -->
		<div class="modal fade" id="add_department">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Department</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('departments')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Department Name</label>
										<input type="text" class="form-control" >
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between">
									<div class="status-title">
										<h5>Status</h5>
										<p>Change the Status by toggle </p>
									</div>
									<div class="form-check form-switch">
										<input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
									</div>
								</div>
							</div>
						</div>

						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Department</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Add Department -->

		<!-- Edit Department -->
		<div class="modal fade" id="edit_department">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Department</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('departments')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Department Name</label>
										<input type="text" class="form-control" placeholder="Enter Department Name"
											value="Admin">
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between">
									<div class="status-title">
										<h5>Status</h5>
										<p>Change the Status by toggle </p>
									</div>
									<div class="form-check form-switch">
										<input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
									</div>
								</div>
							</div>
						</div>

						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Edit Department -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('departments')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->
@endif

@if (Route::is(['designation']))
   	<!-- Add Designation -->
		<div class="modal fade" id="add_designation">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Designation</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('designation')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Designation</label>
										<input type="text" class="form-control" placeholder="Enter Designation">
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between">
									<div class="status-title">
										<h5>Status</h5>
										<p>Change the Status by toggle </p>
									</div>
									<div class="form-check form-switch">
										<input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
									</div>
								</div>
							</div>
						</div>

						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Designation</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Add Designation -->

		<!-- Edit Designation -->
		<div class="modal fade" id="edit_designation">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Designation</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('designation')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Designation</label>
										<input type="text" class="form-control" placeholder="Enter Designation"
											value="Teacher">
									</div>
								</div>
								<div class="d-flex align-items-center justify-content-between">
									<div class="status-title">
										<h5>Status</h5>
										<p>Change the Status by toggle </p>
									</div>
									<div class="form-check form-switch">
										<input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
									</div>
								</div>
							</div>
						</div>

						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Edit Department -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('designation')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->
@endif

@if (Route::is(['list-leaves']))
   <!-- Add Leaves -->
		<div class="modal fade" id="add_leaves">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Leave Type</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('list-leaves')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Leave Type</label>
										<input type="text" class="form-control" placeholder="Enter Leave Type">
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="form-check form-switch">
											<input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Leave Type</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Add Leaves -->

		<!-- Edit Leaves -->
		<div class="modal fade" id="edit_leaves">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Leave Type</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('list-leaves')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Leave Type</label>
										<input type="text" class="form-control" placeholder="Enter Leave Type"
											value="Medical Leave">
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="form-check form-switch">
											<input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Edit Leaves -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('list-leaves')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3"
									data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->
@endif

@if (Route::is(['approve-request']))
 <!-- Leave Request -->
 <div class="modal fade" id="leave_request">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Leave Request</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('approve-request')}}">
                <div class="modal-body">
                    <div class="student-leave-info">
                        <ul>
                            <li>
                                <span>Submitted By</span>
                                <h6>James Deckar</h6>
                            </li>
                            <li>
                                <span>ID / Roll No</span>
                                <h6>9004</h6>
                            </li>
                            <li>
                                <span>Role</span>
                                <h6>Student</h6>
                            </li>
                            <li>
                                <span>Leave Type</span>
                                <h6>Medical Leave</h6>
                            </li>
                            <li>
                                <span>No of Days</span>
                                <h6>2</h6>
                            </li>
                            <li>
                                <span>Applied On</span>
                                <h6>04 May 2024</h6>
                            </li>
                            <li>
                                <span>Authoity</span>
                                <h6>Jacquelin</h6>
                            </li>
                            <li>
                                <span>Leave</span>
                                <h6>05 May 2024 - 07 may 2024</h6>
                            </li>
                        </ul>
                    </div>
                    <div class="mb-3 leave-reason">
                        <h6 class="mb-1">Reason</h6>
                        <span>Headache & fever</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Approval Status</label>
                        <div class="d-flex align-items-center check-radio-group">
                            <label class="custom-radio">
                                <input type="radio" name="radio" checked="">
                                <span class="checkmark"></span>
                                Pending
                            </label>
                            <label class="custom-radio">
                                <input type="radio" name="radio">
                                <span class="checkmark"></span>
                                Approved
                            </label>
                            <label class="custom-radio">
                                <input type="radio" name="radio">
                                <span class="checkmark"></span>
                                Disapproved
                            </label>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Note</label>
                        <textarea class="form-control" placeholder="Add Comment" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Leave Request -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('approve-request')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->

@endif

@if (Route::is(['holidays']))
   <!-- Add Holiday -->
		<div class="modal fade" id="add_holiday">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Holiday</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('holidays')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Holiday Title</label>
										<input type="text" class="form-control" >
									</div>
									<div class="mb-3">
										<label class="form-label">Date</label>
										<input type="text" class="form-control" >
									</div>
									<div class="mb-3">
										<label class="form-label">Description</label>
										<textarea rows="4" class="form-control" ></textarea>
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="form-check form-switch">
											<input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Holiday</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Add Holiday -->

		<!-- Edit Holiday -->
		<div class="modal fade" id="edit_holiday">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Holiday</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('holidays')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Holiday Title</label>
										<input type="text" class="form-control" placeholder="Enter Holiday Title"
											value="New Year">
									</div>
									<div class="mb-3">
										<label class="form-label">Date</label>
										<input type="text" class="form-control" placeholder="Enter Date"
											value="01 Jan 2024">
									</div>
									<div class="mb-3">
										<label class="form-label">Description</label>
										<textarea rows="4" class="form-control"
											placeholder="Add Comment">First day of the new year</textarea>
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<div class="status-title">
											<h5>Status</h5>
											<p>Change the Status by toggle </p>
										</div>
										<div class="form-check form-switch">
											<input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Edit Holiday -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('holidays')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3"
									data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

@endif

@if (Route::is(['expenses']))
    <!-- Add Expenses -->
		<div class="modal fade" id="add_expenses">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Expense</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('expenses')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Expense Name</label>
										<input type="text" class="form-control" >
									</div>
									<div class="mb-3">
										<label class="form-label">Category</label>
										<select class="select">
											<option>Select</option>
											<option>Utilities</option>
											<option>Maintenance</option>
											<option>Sports</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Date</label>
										<input type="text" class="form-control">
									</div>
									<div class="mb-3">
										<label class="form-label">Amount</label>
										<input type="text" class="form-control" >
									</div>
									<div class="mb-3">
										<label class="form-label">Invoice No</label>
										<input type="text" class="form-control">
									</div>
									<div class="mb-3">
										<label class="form-label">Payment Method</label>
										<select class="select">
											<option>Select</option>
											<option>Cash</option>
											<option>Credit</option>
										</select>
									</div>
									<div class="mb-0">
										<label class="form-label">Description</label>
										<textarea rows="4" class="form-control" ></textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Expense</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Expenses -->

		<!-- Edit Expenses -->
		<div class="modal fade" id="edit_expenses">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Expense</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('expenses')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Expense Name</label>
										<input type="text" class="form-control" placeholder="Enter Expense Name"
											value="Monthly Electricity">
									</div>
									<div class="mb-3">
										<label class="form-label">Category</label>
										<select class="select">
											<option>Select</option>
											<option selected>Utilities</option>
											<option>Maintenance</option>
											<option>Sports</option>
										</select>
									</div>
									<div class="mb-3">
										<label class="form-label">Date</label>
										<input type="text" class="form-control" placeholder="Enter Date"
											value="25 Apr 2024">
									</div>
									<div class="mb-3">
										<label class="form-label">Amount</label>
										<input type="text" class="form-control" placeholder="Enter Amount"
											value="$1000">
									</div>
									<div class="mb-3">
										<label class="form-label">Invoice No</label>
										<input type="text" class="form-control" placeholder="Enter Invoice No"
											value="INV681537">
									</div>
									<div class="mb-3">
										<label class="form-label">Payment Method</label>
										<select class="select">
											<option>Select</option>
											<option selected>Cash</option>
											<option>Credit</option>
										</select>
									</div>
									<div class="mb-0">
										<label class="form-label">Description</label>
										<textarea rows="4" class="form-control"
											placeholder="text">Electricity of April month</textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Expenses -->

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('expenses')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3"
									data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->

@endif

@if (Route::is(['expenses-category']))
<!-- Add Expenses Category -->
<div class="modal fade" id="add_expenses_category">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Category</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('expenses-category')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Category </label>
                                <input type="text" class="form-control" placeholder="Enter Category ">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Description</label>
                                <textarea rows="4" class="form-control" placeholder="text"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Expenses Category -->

<!-- Edit Expenses Category -->
<div class="modal fade" id="edit_expenses_category">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Category</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('expenses-category')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Category </label>
                                <input type="text" class="form-control" placeholder="Enter Category"
                                    value="Utilities">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Description</label>
                                <textarea rows="4" class="form-control"
                                    placeholder="text">Expenses related to electricity, water, and gas</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Edit Expenses Category -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('expenses-category')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->

@endif
@if (Route::is(['edit-invoice']))

		<!-- Delete Modal -->
		<div class="modal fade" id="delete-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<form action="{{url('edit-invoice')}}">
						<div class="modal-body text-center">
							<span class="delete-icon">
								<i class="ti ti-trash-x"></i>
							</span>
							<h4>Confirm Deletion</h4>
							<p>You want to delete all the marked items, this cant be undone once you delete.</p>
							<div class="d-flex justify-content-center">
								<a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-danger">Yes, Delete</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Delete Modal -->
@endif
@if (Route::is(['accounts-invoices']))
<!-- View Modal -->
<div class="modal fade" id="view_invoice">
    <div class="modal-dialog modal-dialog-centered  modal-xl invoice-modal">
        <div class="modal-content">
            <div class="modal-wrapper">
                <div class="invoice-popup-head d-flex align-items-center justify-content-between mb-4">
                    <span><img src="{{URL::asset('build/img/logo.svg')}}" alt="Img"></span>
                    <div class="popup-title">
                        <h2>UNIVERSITY NAME</h2>
                        <p>Original For Recipient</p>
                    </div>
                </div>
                <div class="tax-info mb-2">
                    <div class="mb-4 text-center">
                        <h1>Tax Invoice</h1>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Student Name :</h5>
                                <h6>Walter Roberson</h6>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Student ID :</h5>
                                <h6>DD465123</h6>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Term :</h5>
                                <h6>Term 1</h6>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Invoice No :</h5>
                                <h6>INV681531</h6>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Invoice Date :</h5>
                                <h6>24 Apr 2024</h6>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Due Date :</h5>
                                <h6>30 Apr 2024</h6>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <h6 class="mb-1">Bill To :</h6>
                        <p><span class="text-dark">Walter Roberson</span> <br>
                            299 Star Trek Drive, Panama City, Florida, 32405, USA. <br>
                            walter@gmail.com <br>
                            +45 5421 4523
                        </p>
                    </div>
                    <div class="invoice-product-table">
                        <div class="table-responsive invoice-table">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                    </tr>

                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            Semester Fees
                                        </td>
                                        <td>
                                            25 Apr 2024
                                        </td>
                                        <td>
                                            $5,000
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Exam Fees
                                        </td>
                                        <td>
                                            25 Apr 2024
                                        </td>
                                        <td>
                                            $1000
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Transport Fees
                                        </td>
                                        <td>
                                            25 Apr 2024
                                        </td>
                                        <td>
                                            $4,000
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <h5 class="mb-1">Important Note: </h5>
                                <p class="text-dark mb-0">Delivery dates are not guaranteed and Seller has</p>
                                <p class="text-dark">no liability for damages that may be incurred
                                    due to any delay. has
                                </p>
                            </div>
                            <div>
                                <h5 class="mb-1">Total amount ( in words):</h5>
                                <p class="text-dark fw-medium">USD Ten Thousand One Hundred Sixty Five Only</p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="total-amount-tax">
                                <ul>
                                    <li class="fw-medium text-dark">Subtotal</li>

                                    <li class="fw-medium text-dark">Discount 0%</li>

                                    <li class="fw-medium text-dark">IGST 18.0%</li>

                                </ul>
                                <ul>
                                    <li>$10,000.00</li>
                                    <li>+ $0.00</li>
                                    <li>$10,000.00</li>
                                </ul>
                            </div>
                            <div class="total-amount-tax mb-3">
                                <ul class="total-amount">
                                    <li class="text-dark">Amount Payable</li>
                                </ul>
                                <ul class="total-amount">
                                    <li class="text-dark">$10,165.00</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="payment-info">
                        <div class="row align-items-center">
                            <div class="col-lg-6 mb-4 pt-4">
                                <h5 class="mb-2">Payment Info:</h5>
                                <p class="mb-1">Debit Card : <span class="fw-medium text-dark">465 *************645</span></p>
                                <p class="mb-0">Amount : <span class="fw-medium text-dark">$10,165</span></p>
                            </div>
                            <div class="col-lg-6 text-end mb-4 pt-4 ">
                                <h6 class="mb-2">For Dreamguys</h6>
                                <img src="{{URL::asset('build/img/icons/signature.svg')}}" alt="Img">
                            </div>
                        </div>
                    </div>
                    <div class="border-bottom text-center pt-4 pb-4">
                        <span class="text-dark fw-medium">Terms & Conditions : </span>
                        <p>Here we can write a additional notes for the client to get a better understanding of
                            this invoice.</p>
                    </div>
                    <p class="text-center pt-3">Thanks for your Business</p>
                </div>

            </div>
        </div>
    </div>
</div>
<!-- /View Modal -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('accounts-invoices')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->

@endif
@if (Route::is(['add-invoice']))
<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('add-invoice')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->
@endif
@if (Route::is(['accounts-income']))
<!-- Add Income -->
<div class="modal fade" id="add_income">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Income</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('accounts-income')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Income Name</label>
                                <input type="text" class="form-control" placeholder="Enter Income Name">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Source</label>
                                <input type="text" class="form-control" placeholder="Enter Source">
                            </div>

                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="text" class="form-control datetimepicker" value="15/12/2003">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="text" class="form-control" placeholder="Enter Amount">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Invoice No</label>
                                <input type="text" class="form-control" placeholder="Enter Invoice No">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Cash</option>
                                    <option>Credit</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-0">
                                <label class="form-label">Description</label>
                                <textarea rows="4" class="form-control" placeholder="text"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Income</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Income -->

<!-- Edit Income -->
<div class="modal fade" id="edit_income">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Income</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('accounts-income')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Income Name</label>
                                <input type="text" class="form-control" placeholder="Enter Income Name" value="April Month Fees">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Source</label>
                                <input type="text" class="form-control" placeholder="Enter Source" value="Tuition Fees">
                            </div>

                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="text" class="form-control datetimepicker" value="15/12/1992">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="text" class="form-control" placeholder="Enter Amount" value="$15,000">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Invoice No</label>
                                <input type="text" class="form-control" placeholder="Enter Invoice No" value="INV681537">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option selected>Cash</option>
                                    <option>Credit</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-0">
                                <label class="form-label">Description</label>
                                <textarea rows="4" class="form-control" placeholder="text">Tuition for Term 1, Class II</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Edit Income -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('accounts-income')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->
<!-- View Modal -->
<div class="modal fade" id="view_invoice">
    <div class="modal-dialog modal-dialog-centered  modal-xl invoice-modal">
        <div class="modal-content">
            <div class="modal-wrapper">
                <div class="invoice-popup-head d-flex align-items-center justify-content-between">
                    <span><img src="{{URL::asset('build/img/logo.svg')}}" alt="Img"></span>
                    <div class="popup-title">
                        <h2>UNIVERSITY NAME</h2>
                        <p>Original For Recipient</p>
                    </div>
                </div>
                <div class="tax-info">
                    <h1>Tax Invoice</h1>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Student Name :</h5>
                                <h4>Walter Roberson</h4>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Student ID :</h5>
                                <h4>DD465123</h4>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Term :</h5>
                                <h4>Term 1</h4>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Invoice No :</h5>
                                <h4>INV681531</h4>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Invoice Date :</h5>
                                <h4>24 Apr 2024</h4>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="tax-invoice-info d-flex align-items-center justify-content-between">
                                <h5>Due Date :</h5>
                                <h4>30 Apr 2024</h4>
                            </div>
                        </div>
                    </div>
                    <div class="billing-info">
                        <span>Bill To :</span>
                        <p>Walter Roberson <br>
                            299 Star Trek Drive, Panama City, Florida, 32405, USA. <br>
                            walter@gmail.com <br>
                            +45 5421 4523
                        </p>
                    </div>
                    <div class="invoice-product-table">
                        <div class="table-responsive invoice-table">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                    </tr>

                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            Semester Fees
                                        </td>
                                        <td>
                                            25 Apr 2024
                                        </td>
                                        <td>
                                            $5,000
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Exam Fees
                                        </td>
                                        <td>
                                            25 Apr 2024
                                        </td>
                                        <td>
                                            $1000
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Transport Fees
                                        </td>
                                        <td>
                                            25 Apr 2024
                                        </td>
                                        <td>
                                            $4,000
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="billing-info">
                                <h5 class="text-default">Important Note: </h5>
                                <p class="text-dark mb-0">Delivery dates are not guaranteed and Seller has</p>
                                <p class="text-dark">no liability for damages that may be incurred
                                    due to any delay. has
                                </p>
                            </div>
                            <div class="billing-info">
                                <h5 class="text-default">Total amount ( in words):</h5>
                                <p class="text-dark">USD Ten Thousand One Hundred Sixty Five Only</p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="total-amount-tax">
                                <ul>
                                    <li>Subtotal</li>

                                    <li>Discount 0%</li>

                                    <li>IGST 18.0%</li>

                                </ul>
                                <ul>
                                    <li>$10,000.00</li>
                                    <li>+ $0.00</li>
                                    <li>$10,000.00</li>
                                </ul>
                            </div>
                            <div class="total-amount-tax">
                                <ul class="total-amount">
                                    <li>Amount Payable</li>
                                </ul>
                                <ul class="total-amount">
                                    <li>$10,165.00</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="payment-info">
                        <div class="row align-items-center">
                            <div class="col-lg-6 mb-4 pt-4">
                                <h4 class="mb-2">Payment Info:</h4>
                                <h6><span>Debit Card :</span>465 *************645</h6>
                                <h6 class="mb-0"><span>Amount :</span>$10,165</h6>
                            </div>
                            <div class="col-lg-6 text-end mb-4 pt-4 ">
                                <h6 class="mb-2">For Dreamguys</h6>
                                <img src="{{URL::asset('build/img/icons/signature.svg')}}" alt="Img">
                            </div>
                        </div>
                    </div>
                    <div class="term-condition-invoice text-center pt-4 pb-4">
                        <span>Terms & Conditions : </span>
                        <p>Here we can write a additional notes for the client to get a better understanding of
                            this invoice.</p>
                    </div>
                    <h6 class="thanks-text">Thanks for your Business</h6>
                </div>

            </div>
        </div>
    </div>
</div>
<!-- /View Modal -->
@endif
@if (Route::is(['library-issue-book']))
	<!-- Book Details -->
    <div class="modal fade" id="book_details">
        <div class="modal-dialog modal-dialog-centered  modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">View Details</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="view-book">
                        <div class="view-book-title">
                            <h5>Issue Book Details</h5>
                        </div>
                        <div class="book-issue-details">
                            <div class="book-details-head">
                                <span class="text-primary">IB853629</span>
                                <h6><span>Issue Date :</span> 19 May 2024</h6>
                            </div>
                            <ul class="book-taker-info">
                                <li>
                                    <div class="d-flex align-items-center">
                                        <span class="student-img">
                                            <img src="{{URL::asset('build/img/students/student-01.jpg')}}" class="img-fluid rounded-circle" alt="Img">
                                        </span>
                                        <h6>Janet <br> III, A</h6>
                                    </div>
                                </li>
                                <li>
                                    <span>Roll No</span>
                                    <h6>35010</h6>
                                </li>
                                <li>
                                    <span>Book Name</span>
                                    <h6>Echoes of Eternity</h6>
                                </li>
                                <li>
                                    <span>Book No</span>
                                    <h6>501</h6>
                                </li>
                                <li>
                                    <span>Due Date</span>
                                    <h6>19 May 2024</h6>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Book Details -->
@endif

@if (Route::is(['notice-board']))
   	<!-- Add Messase -->
		<div class="modal fade" id="add_message">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">New Message</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('notice-board')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Title</label>
										<input type="text" class="form-control">
									</div>
									<div class="mb-3">
										<label class="form-label">Notice Date</label>
										<div class="date-pic">
											<input type="text" class="form-control datetimepicker">
											<span class="cal-icon"><i class="ti ti-calendar"></i></span>
										</div>
									</div>
									<div class="mb-3">
										<label class="form-label">Publish On</label>
										<div class="date-pic">
											<input type="text" class="form-control datetimepicker">
											<span class="cal-icon"><i class="ti ti-calendar"></i></span>
										</div>
									</div>
									<div class="mb-3">
										<div class="bg-light p-3 pb-2 rounded">
											<div class="mb-3">
												<label class="form-label">Attachment</label>
												<p>Upload size of 4MB, Accepted Format PDF</p>
											</div>
											<div class="d-flex align-items-center flex-wrap">
												<div class="btn btn-primary drag-upload-btn mb-2 me-2">
													<i class="ti ti-file-upload me-1"></i>Upload
													<input type="file" class="form-control image_sign" multiple="">
												</div>
											</div>
										</div>
									</div>
									<div class="mb-3">
										<label class="form-label">Message</label>
										<textarea class="form-control" rows="4"></textarea>
									</div>
									<div class="mb-0">
										<label class="form-label">Message To</label>
										<div class="row">
											<div class="col-md-6">
												<label class="checkboxs mb-1">
													<input type="checkbox">
													<span class="checkmarks"></span>
													Student
												</label>
												<label class="checkboxs mb-1">
													<input type="checkbox">
													<span class="checkmarks"></span>
													Parent
												</label>
												<label class="checkboxs mb-1">
													<input type="checkbox">
													<span class="checkmarks"></span>
													Admin
												</label>
												<label class="checkboxs mb-1">
													<input type="checkbox">
													<span class="checkmarks"></span>
													Teacher
												</label>
											</div>
											<div class="col-md-6">
												<label class="checkboxs mb-1">
													<input type="checkbox">
													<span class="checkmarks"></span>
													Accountant
												</label>
												<label class="checkboxs mb-1">
													<input type="checkbox">
													<span class="checkmarks"></span>
													Librarian
												</label>
												<label class="checkboxs mb-1">
													<input type="checkbox">
													<span class="checkmarks"></span>
													Receptionist
												</label>
												<label class="checkboxs mb-1">
													<input type="checkbox">
													<span class="checkmarks"></span>
													Super Admin
												</label>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add New Mesaage</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Messase -->

		<!-- Edit Messase -->
		<div class="modal fade" id="edit_message">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Message</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('notice-board')}}">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div class="mb-3">
										<label class="form-label">Title</label>
										<input type="text" class="form-control" placeholder="Enter Title"
											value="Fees Reminder">
									</div>
									<div class="mb-3">
										<label class="form-label">Notice Date</label>
										<div class="date-pic">
											<input type="text" class="form-control datetimepicker"
												placeholder="15 May 2024">
											<span class="cal-icon"><i class="ti ti-calendar"></i></span>
										</div>
									</div>
									<div class="mb-3">
										<label class="form-label">Publish On</label>
										<div class="date-pic">
											<input type="text" class="form-control datetimepicker"
												placeholder="15 May 2024">
											<span class="cal-icon"><i class="ti ti-calendar"></i></span>
										</div>
									</div>
									<div class="mb-3">
										<div class="bg-light p-3 pb-2 rounded">
											<div class="mb-3">
												<label class="form-label">Attachment</label>
												<p>Upload size of 4MB, Accepted Format PDF</p>
											</div>
											<div class="d-flex align-items-center flex-wrap">
												<div class="btn btn-primary drag-upload-btn mb-2 me-2">
													<i class="ti ti-file-upload me-1"></i>Upload
													<input type="file" class="form-control image_sign" multiple="">
												</div>
												<p class="mb-2">Fees_Structure.pdf</p>
											</div>
										</div>
									</div>
									<div class="mb-3">
										<label class="form-label">Message</label>
										<textarea class="form-control" rows="4"
											placeholder="Add Comment">Please clear the outstanding dues for the school fees on the urgent basis.</textarea>
									</div>
									<div class="mb-0">
										<label class="form-label">Message To</label>
										<div class="row">
											<div class="col-md-6">
												<div class="form-check form-check-md mb-1">
													<input class="form-check-input" type="checkbox">
													<span>Student</span>
												</div>
												<div class="form-check form-check-md mb-1">
													<input class="form-check-input" type="checkbox">
													<span>Parent</span>
												</div>
												<div class="form-check form-check-md mb-1">
													<input class="form-check-input" type="checkbox">
													<span>Admin</span>
												</div>
												<div class="form-check form-check-md">
													<input class="form-check-input" type="checkbox">
													<span>Teacher</span>
												</div>
											</div>
											<div class="col-md-6">
												<div class="form-check form-check-md mb-1">
													<input class="form-check-input" type="checkbox">
													<span>Accountant</span>
												</div>
												<div class="form-check form-check-md mb-1">
													<input class="form-check-input" type="checkbox">
													<span>Librarian</span>
												</div>
												<div class="form-check form-check-md mb-1">
													<input class="form-check-input" type="checkbox">
													<span>Receptionist</span>
												</div>
												<div class="form-check form-check-md">
													<input class="form-check-input" type="checkbox">
													<span>Super Admin</span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Edit Messase -->

		<!-- View Details -->
		<div class="modal fade" id="view_details">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Fees Reminder</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<div class="modal-body pb-0">
						<div class="mb-3">
							<p class="mb-1">Dear parents,</p>
							<p>Please clear the outstanding dues for the school fees on the urgent
								basis.</p>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="mb-3">
									<label class="form-label">Notice Date</label>
									<p class="d-flex align-items-center"><i class="ti ti-calendar me-1"></i>15 May 2024
									</p>
								</div>
							</div>
							<div class="col-md-6">
								<div class="mb-3">
									<label class="form-label">Publish On</label>
									<p class="d-flex align-items-center"><i class="ti ti-calendar me-1"></i>21 May 2024
									</p>
								</div>
							</div>
						</div>
						<div class="mb-3">
							<div class="bg-light p-3 pb-2 rounded">
								<div class="mb-0">
									<label class="form-label">Attachment</label>
									<p class="text-primary">Fees_Structure.pdf</p>
								</div>
							</div>
						</div>
						<div class="mb-3">
							<label class="form-label d-block">Message To</label>
							<span class="badge badge-soft-primary me-2">Student</span>
							<span class="badge badge-soft-primary">Parent</span>
						</div>
						<div class="border-top pt-3">
							<div class="d-flex align-items-center flex-wrap">
								<div class="d-flex align-items-center me-4 mb-3">
									<span class="avatar avatar-sm bg-light me-1"><i
											class="ti ti-calendar text-default fs-14"></i></span>Added on: 28 Apr 2024
								</div>
								<div class="d-flex align-items-center mb-3">
									<span class="avatar avatar-sm bg-light me-1"><i
											class="ti ti-user-edit text-default fs-14"></i></span>Added By
									: Daniel
								</div>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- /View Details -->

			<!-- Delete Modal -->
			<div class="modal fade" id="delete-modal">
				<div class="modal-dialog modal-dialog-centered">
					<div class="modal-content">
						<form action="{{url('notice-board')}}">
							<div class="modal-body text-center">
								<span class="delete-icon">
									<i class="ti ti-trash-x"></i>
								</span>
								<h4>Confirm Deletion</h4>
								<p>You want to delete all the marked items, this cant be undone once you
									delete.</p>
								<div class="d-flex justify-content-center">
									<a href="javascript:void(0);" class="btn btn-light me-3"
										data-bs-dismiss="modal">Cancel</a>
									<button type="submit" class="btn btn-danger">Yes, Delete</button>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
			<!-- /Delete Modal -->

		</div>
		<!-- /Main Wrapper -->



@endif

@if (Route::is(['events']))
  <!-- Add Event -->
  <div class="modal fade" id="add_event">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">New Event</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>					
            <form action="{{url('events')}}">
                <div class="modal-body">		
                    <div class="row">
                        <div class="col-md-12">
                            <div>
                                <label class="form-label">Event For</label>
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="all" checked="">
                                        <label class="form-check-label" for="all">
                                            All
                                        </label>
                                    </div>
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="students">
                                        <label class="form-check-label" for="students">
                                            Students
                                        </label>
                                    </div>
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="staffs">
                                        <label class="form-check-label" for="staffs">
                                            Staffs
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="all-content" id="all-student">
                                <div class="mb-3">
                                    <label class="form-label">Classes</label>
                                    <select class="select">
                                        <option>All Classes</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sections</label>
                                    <select class="select">
                                        <option>All Sections</option>
                                        <option>A</option>
                                        <option>B</option>
                                    </select>
                                </div>
                            </div>
                            <div class="all-content" id="all-staffs">
                                <div class="mb-3">
                                    <div class="bg-light-500 p-3 pb-2 rounded">
                                        <label class="form-label">Role</label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Admin
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox" checked>Teacher
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Driver
                                                </div>										
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Accountant
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Librarian
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Receptionist
                                                </div>											
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">All Teachers</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                            </div>
                        </div>			
                        <div class="mb-3">
                            <label class="form-label">Event Title</label>
                            <input type="text" class="form-control" placeholder="Enter Title">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event Category</label>
                            <select class="select">
                                <option>Select</option>
                                <option>Celebration</option>
                                <option>Training</option>
                                <option>Meeting</option>
                                <option>Holidays</option>
                            </select>
                        </div>						
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="15 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>											
                        </div>									
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="21 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>											
                        </div>								
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Time</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control timepicker" placeholder="09:10 AM">
                                    <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                </div>
                            </div>											
                        </div>							
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Time</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control timepicker" placeholder="12:50 PM">
                                    <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                </div>
                            </div>											
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="bg-light p-3 pb-2 rounded">
                                    <div class="mb-3">
                                        <label class="form-label">Attachment</label>
                                        <p>Upload size of 4MB, Accepted Format PDF</p>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap">
                                        <div class="btn btn-primary drag-upload-btn mb-2 me-2">
                                            <i class="ti ti-file-upload me-1"></i>Upload
                                            <input type="file" class="form-control image_sign" multiple="">
                                        </div>
                                        <p class="mb-2">Fees_Structure.pdf</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" rows="4">Meeting with Staffs on the Quality Improvement s and completion of syllabus before the August,  enhance the students health issue</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Event -->	

<!-- Event Details -->
<div class="modal fade" id="eventModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border shadow-lg">
            <div class="modal-header justify-content-between">
                <span class="d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-8 me-1 text-info"></i>Meeting</span>
                <div class="d-flex align-items-center">
                    <a href="#" class="me-1 fs-18"><i class="ti ti-edit-circle"></i></a>
                    <a href="#" class="me-1 fs-18"><i class="ti ti-trash-x"></i></a>
                    <a href="#" class="fs-18" data-bs-dismiss="modal"><i class="ti ti-x"></i></a>
                </div>
            </div>
            <div class="modal-body pb-0">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar avatar-xl bg-primary-transparent me-3 flex-shrink-0">
                        <i class="ti ti-users-group fs-30"></i>
                    </span>
                    <div>
                        <h3 id="eventTitle" class="mb-1"></h3>
                        <div class="d-flex align-items-center flex-wrap">
                            <p class="me-3 mb-0"><i class="ti ti-calendar me-1"></i>10 July 2024</p>
                            <p><i class="ti ti-calendar me-1"></i>09:10AM - 10:50PM</p>
                        </div>
                    </div>
                </div>
                <div class="bg-light-400 p-3 rounded mb-3">
                    <p>Meeting with Staffs on the Quality Improvement s and completion of syllabus before the August,  enhance the students health issue</p>
                </div>
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div class="avatar-list-stacked avatar-group-sm d-flex mb-3">
                        <span class="avatar">
                            <img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" alt="img">
                        </span>
                        <span class="avatar">
                            <img src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}" alt="img">
                        </span>
                        <span class="avatar">
                            <img src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" alt="img">
                        </span>
                        <a class="avatar bg-white text-default" href="javascript:void(0);">
                            +67
                        </a>
                    </div>
                    <div class="mb-3">
                        <p class="mb-1">Event For</p>
                        <h6>All Classes,  All Sections</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Event Details -->
@endif
@if (Route::is(['blog']))
<!-- Add Blog -->
<div class="modal fade" id="add_blog">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">New Blogs</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>					
            <form action="{{url('blog')}}">
                <div class="modal-body">		
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Blog Title</label>
                                <input type="text" class="form-control" placeholder="Enter Title">
                            </div>
                            <label class="form-label">Featured Image</label>	
                            <div class="d-flex align-items-center upload-pic flex-wrap row-gap-3 mb-3">												
                                <div class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                                    <i class="ti ti-photo-plus fs-16"></i>
                                </div>												
                                <div class="profile-upload">
                                    <div class="profile-uploader d-flex align-items-center">
                                        <div class="drag-upload-btn mb-3">
                                            Upload
                                            <input type="file" class="form-control image-sign" multiple="">
                                        </div>
                                        <a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
                                    </div>
                                    <p>Upload image size 4MB, Format JPG, PNG, SVG</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="select">
                                    <option>Selct</option>
                                    <option>Education</option>
                                    <option>Advice</option>
                                    <option>Academic</option>
                                    <option>Clear Goals</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tags</label>
                                <select class="select">
                                    <option>Selct</option>
                                    <option>Guide</option>
                                    <option>Development</option>
                                    <option>Education</option>
                                    <option>Sports</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="select">
                                    <option>Selct</option>
                                    <option>Active</option>
                                    <option>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-0">
                                <label class="form-label">Description</label>
                                <div class="summernote"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Blog</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Blog -->	

<!-- Edit Blog -->
<div class="modal fade" id="edit_blog">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Blogs</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>					
            <form action="{{url('blog')}}">
                <div class="modal-body">		
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Blog Title</label>
                                <input type="text" class="form-control" placeholder="Enter Title" value="Personal development and self-improvement">
                            </div>	
                            <label class="form-label">Featured Image</label>						
                            <div class="d-flex align-items-center upload-pic flex-wrap row-gap-3 mb-3">												
                                <div class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                                    <i class="ti ti-photo-plus fs-16"></i>
                                </div>												
                                <div class="profile-upload">
                                    <div class="profile-uploader d-flex align-items-center">
                                        <div class="drag-upload-btn mb-3">
                                            Upload
                                            <input type="file" class="form-control image-sign" multiple="">
                                        </div>
                                        <a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
                                    </div>
                                    <p>Upload image size 4MB, Format JPG, PNG, SVG</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="select">
                                    <option>Selct</option>
                                    <option>Education</option>
                                    <option selected>Advice</option>
                                    <option>Academic</option>
                                    <option>Clear Goals</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tags</label>
                                <select class="select">
                                    <option>Selct</option>
                                    <option>Guide</option>
                                    <option selected>Development</option>
                                    <option>Education</option>
                                    <option>Sports</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="select">
                                    <option>Selct</option>
                                    <option selected>Active</option>
                                    <option>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-0">
                                <label class="form-label">Description</label>
                                <div class="summernote">Introduction: Are you struggling to stay focused and get things done? Do you find yourself procrastinating or feeling overwhelmed by your workload? You're not alone. In today's fast-paced world, maintaining productivity can be a challenge. But fear not! In this article, we'll share five proven strategies to help you boost your productivity and reclaim control of your time.
                                    1. Set Clear Goals: One of the most effective ways to increase productivity is to set clear, achievable goals. Start by defining what you want to accomplish, and break down your goals into smaller, manageable tasks. This will help you stay focused and motivated as you work towards achieving your objectives.
                                    2. Prioritize Your Tasks: Not all tasks are created equal. To maximize your productivity, it's essential to prioritize your tasks based on their importance and urgency. Identify the most critical tasks that align with your goals and focus your time and energy on completing them first. This will prevent you from getting bogged down by less important tasks and ensure that you're making progress towards your objectives.
                                    3. Eliminate Distractions: Distractions can derail your productivity and prevent you from staying focused on your work. Identify common distractions in your environment, such as social media, email notifications, or noisy surroundings, and take steps to minimize or eliminate them. This could involve turning off notifications, setting designated work hours, or creating a distraction-free workspace where you can concentrate without interruption.
                                    4. Use Time Management Techniques: Effective time management is key to enhancing productivity. Experiment with different time management techniques, such as the Pomodoro Technique, time blocking, or the Eisenhower Matrix, to find a system that works best for you. By allocating your time wisely and staying organized, you can maximize your efficiency and accomplish more in less time.
                                    5. Take Regular Breaks: Contrary to popular belief, taking regular breaks can actually improve productivity. Research has shown that short breaks can help refresh your mind, prevent burnout, and enhance focus and concentration. Incorporate short breaks into your workday, and use them to rest, recharge, and rejuvenate. Whether it's going for a walk, practicing mindfulness, or simply taking a few deep breaths, find activities that help you relax and unwind before diving back into your work.
                                    Conclusion: Boosting productivity is not an overnight process, but with dedication and consistent effort, you can develop habits and strategies that will help you work smarter, not harder. By setting clear goals, prioritizing tasks, eliminating distractions, managing your time effectively, and taking regular breaks, you can unlock your full potential and achieve greater success in both your personal and professional life.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Edit Blog -->	

@endif
@if (Route::is(['blog-categories']))
	<!-- Add Category -->
    <div class="modal fade" id="add_category">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Category</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('blog-categories')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Category Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Category">
                                </div>
                                <div class="modal-satus-toggle d-flex align-items-center justify-content-between">
                                    <div class="status-title">
                                        <h5>Status</h5>
                                        <p>Change the Status by toggle </p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Add Category -->

    <!-- Edit Category -->
    <div class="modal fade" id="edit_category">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Category</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('blog-categories')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Category Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Category"
                                        value="Advice">
                                </div>
                                <div class="modal-satus-toggle d-flex align-items-center justify-content-between">
                                    <div class="status-title">
                                        <h5>Status</h5>
                                        <p>Change the Status by toggle </p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Category -->

    <!-- Delete Modal -->
    <div class="modal fade" id="delete-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{url('blog-categories')}}">
                    <div class="modal-body text-center">
                        <span class="delete-icon">
                            <i class="ti ti-trash-x"></i>
                        </span>
                        <h4>Confirm Deletion</h4>
                        <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                        <div class="d-flex justify-content-center">
                            <a href="javascript:void(0);" class="btn btn-light me-3"
                                data-bs-dismiss="modal">Cancel</a>
                            <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Delete Modal -->
@endif
@if (Route::is(['blog-comments']))
<!-- Add Blog -->
<div class="modal fade" id="add_blog">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">New Blogs</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('blog')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Blog Title</label>
                                <input type="text" class="form-control" placeholder="Enter Title">
                            </div>
                            <label class="form-label">Featured Image</label>	
                            <div class="d-flex align-items-center upload-pic flex-wrap row-gap-3 mb-3">												
                                <div class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                                    <i class="ti ti-photo-plus fs-16"></i>
                                </div>												
                                <div class="profile-upload">
                                    <div class="profile-uploader d-flex align-items-center">
                                        <div class="drag-upload-btn mb-3">
                                            Upload
                                            <input type="file" class="form-control image-sign" multiple="">
                                        </div>
                                        <a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
                                    </div>
                                    <p>Upload image size 4MB, Format JPG, PNG, SVG</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="select">
                                    <option>Selct</option>
                                    <option>Education</option>
                                    <option>Advice</option>
                                    <option>Academic</option>
                                    <option>Clear Goals</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tags</label>
                                <select class="select">
                                    <option>Selct</option>
                                    <option>Guide</option>
                                    <option>Development</option>
                                    <option>Education</option>
                                    <option>Sports</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="select">
                                    <option>Selct</option>
                                    <option>Active</option>
                                    <option>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-0">
                                <label class="form-label">Description</label>
                                <div class="summernote"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Blog</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Blog -->

<!-- Edit Blog -->
<div class="modal fade" id="edit_blog">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Blogs</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('blog')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Blog Title</label>
                                <input type="text" class="form-control" placeholder="Enter Title" value="Personal development and self-improvement">
                            </div>	
                            <label class="form-label">Featured Image</label>						
                            <div class="d-flex align-items-center upload-pic flex-wrap row-gap-3 mb-3">												
                                <div class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                                    <i class="ti ti-photo-plus fs-16"></i>
                                </div>												
                                <div class="profile-upload">
                                    <div class="profile-uploader d-flex align-items-center">
                                        <div class="drag-upload-btn mb-3">
                                            Upload
                                            <input type="file" class="form-control image-sign" multiple="">
                                        </div>
                                        <a href="javascript:void(0);" class="btn btn-primary mb-3">Remove</a>
                                    </div>
                                    <p>Upload image size 4MB, Format JPG, PNG, SVG</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="select">
                                    <option>Selct</option>
                                    <option>Education</option>
                                    <option selected>Advice</option>
                                    <option>Academic</option>
                                    <option>Clear Goals</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tags</label>
                                <select class="select">
                                    <option>Selct</option>
                                    <option>Guide</option>
                                    <option selected>Development</option>
                                    <option>Education</option>
                                    <option>Sports</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="select">
                                    <option>Selct</option>
                                    <option selected>Active</option>
                                    <option>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-0">
                                <label class="form-label">Description</label>
                                <div class="summernote">Introduction: Are you struggling to stay focused and get
                                    things done? Do you find yourself procrastinating or feeling overwhelmed by
                                    your workload? You're not alone. In today's fast-paced world, maintaining
                                    productivity can be a challenge. But fear not! In this article, we'll share
                                    five proven strategies to help you boost your productivity and reclaim
                                    control of your time.
                                    1. Set Clear Goals: One of the most effective ways to increase productivity
                                    is to set clear, achievable goals. Start by defining what you want to
                                    accomplish, and break down your goals into smaller, manageable tasks. This
                                    will help you stay focused and motivated as you work towards achieving your
                                    objectives.
                                    2. Prioritize Your Tasks: Not all tasks are created equal. To maximize your
                                    productivity, it's essential to prioritize your tasks based on their
                                    importance and urgency. Identify the most critical tasks that align with
                                    your goals and focus your time and energy on completing them first. This
                                    will prevent you from getting bogged down by less important tasks and ensure
                                    that you're making progress towards your objectives.
                                    3. Eliminate Distractions: Distractions can derail your productivity and
                                    prevent you from staying focused on your work. Identify common distractions
                                    in your environment, such as social media, email notifications, or noisy
                                    surroundings, and take steps to minimize or eliminate them. This could
                                    involve turning off notifications, setting designated work hours, or
                                    creating a distraction-free workspace where you can concentrate without
                                    interruption.
                                    4. Use Time Management Techniques: Effective time management is key to
                                    enhancing productivity. Experiment with different time management
                                    techniques, such as the Pomodoro Technique, time blocking, or the Eisenhower
                                    Matrix, to find a system that works best for you. By allocating your time
                                    wisely and staying organized, you can maximize your efficiency and
                                    accomplish more in less time.
                                    5. Take Regular Breaks: Contrary to popular belief, taking regular breaks
                                    can actually improve productivity. Research has shown that short breaks can
                                    help refresh your mind, prevent burnout, and enhance focus and
                                    concentration. Incorporate short breaks into your workday, and use them to
                                    rest, recharge, and rejuvenate. Whether it's going for a walk, practicing
                                    mindfulness, or simply taking a few deep breaths, find activities that help
                                    you relax and unwind before diving back into your work.
                                    Conclusion: Boosting productivity is not an overnight process, but with
                                    dedication and consistent effort, you can develop habits and strategies that
                                    will help you work smarter, not harder. By setting clear goals, prioritizing
                                    tasks, eliminating distractions, managing your time effectively, and taking
                                    regular breaks, you can unlock your full potential and achieve greater
                                    success in both your personal and professional life.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Edit Blog -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('blog-comments')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->	
@endif
@if (Route::is(['blog-tags']))
<!-- Add Tags -->
<div class="modal fade" id="add_tags">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Tags</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('blog-tags')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Tag Name</label>
                                <input type="text" class="form-control" placeholder="Enter Category">
                            </div>	
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Tag</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Tags -->

<!-- Edit Tags -->
<div class="modal fade" id="edit_tags">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Tags</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('blog-tags')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Tag Name</label>
                                <input type="text" class="form-control" placeholder="Enter Tag" value="Sports">
                            </div>	
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-title">
                                    <h5>Status</h5>
                                    <p>Change the Status by toggle </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switch-sm2">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Edit Tags -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('blog-tags')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->


@endif
@if (Route::is(['membership-plans']))
		<!-- Add Plan -->
		<div class="modal fade" id="add_membership">
			<div class="modal-dialog modal-dialog-centered  modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Plan</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
							aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="{{url('membership-plans')}}">
						<div class="modal-body pb-0">
							<div class="row">
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Plan Name</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Type</label>
										<select class="select">
											<option>Select</option>
											<option>Starter Pack</option>
											<option>Enterprise</option>
											<option>Premium Pack</option>
										</select>
									</div>
								</div>
								<div class="col-lg-4 col-md-6">
									<div class="mb-3">
										<label class="form-label">Plan Price</label>
										<input type="text" class="form-control">
									</div>
								</div>
								<div class="col-md-12">
									<h5 class="mb-3">Plan Settings</h5>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Students & Teachers</label>
										<div class=" d-flex align-items-center mb-3">
											<div class="w-100 me-3">
												<input type="text" class="form-control">
											</div>
											<div class="status-toggle modal-status">
												<input type="checkbox" id="plan" class="check">
												<label for="plan" class="checktoggle"> </label>
											</div>
										</div>
										<label class="checkboxs">
											<input type="checkbox">
											<span class="checkmarks"></span>Unlimited
										</label>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Classes & Sections</label>
										<div class=" d-flex align-items-center mb-3">
											<div class="w-100 me-3">
												<input type="text" class="form-control">
											</div>
											<div class="status-toggle modal-status">
												<input type="checkbox" id="plan1" class="check">
												<label for="plan1" class="checktoggle"> </label>
											</div>
										</div>
										<label class="checkboxs">
											<input type="checkbox">
											<span class="checkmarks"></span>Unlimited
										</label>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Subjects & Exams</label>
										<div class=" d-flex align-items-center mb-3">
											<div class="w-100 me-3">
												<input type="text" class="form-control">
											</div>
											<div class="status-toggle modal-status">
												<input type="checkbox" id="plan2" class="check">
												<label for="plan2" class="checktoggle"> </label>
											</div>
										</div>
										<label class="checkboxs">
											<input type="checkbox">
											<span class="checkmarks"></span>Unlimited
										</label>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Departments</label>
										<div class=" d-flex align-items-center mb-3">
											<div class="w-100 me-3">
												<input type="text" class="form-control">
											</div>
											<div class="status-toggle modal-status">
												<input type="checkbox" id="plan3" class="check">
												<label for="plan3" class="checktoggle"> </label>
											</div>
										</div>
										<label class="checkboxs">
											<input type="checkbox">
											<span class="checkmarks"></span>Unlimited
										</label>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Designations</label>
										<div class=" d-flex align-items-center mb-3">
											<div class="w-100 me-3">
												<input type="text" class="form-control">
											</div>
											<div class="status-toggle modal-status">
												<input type="checkbox" id="plan4" class="check">
												<label for="plan4" class="checktoggle"> </label>
											</div>
										</div>
										<label class="checkboxs">
											<input type="checkbox">
											<span class="checkmarks"></span>Unlimited
										</label>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Library & Transport</label>
										<div class=" d-flex align-items-center mb-3">
											<div class="w-100 me-3">
												<input type="text" class="form-control">
											</div>
											<div class="status-toggle modal-status">
												<input type="checkbox" id="plan5" class="check">
												<label for="plan5" class="checktoggle"> </label>
											</div>
										</div>
										<label class="checkboxs">
											<input type="checkbox">
											<span class="checkmarks"></span>Unlimited
										</label>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
							<button type="submit" class="btn btn-primary">Add Plan</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Plan -->

@endif
@if (Route::is(['membership-transactions']))
<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('membership-transactions')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->

@endif
@if (Route::is(['roles-permission']))
<!-- Add Role -->
<div class="modal fade" id="add_role">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Role</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('roles-permission')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-0">
                                <label class="form-label">Role Name</label>
                                <input type="text" class="form-control" placeholder="Enter State Name">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Role</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Role -->

<!-- Edit Role -->
<div class="modal fade" id="edit_role">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Role</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('roles-permission')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-0">
                                <label class="col-form-label">Role Name</label>
                                <input type="text" class="form-control" placeholder="Enter State Name"
                                    value="Admin">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Edit Role -->

<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('roles-permission')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->
			

@endif
@if (Route::is(['layout-mini']))
<!-- Add Class Routine -->
<div class="modal fade" id="add_class_routine">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-wrapper">
                <div class="modal-header">
                    <h4 class="modal-title">Add Class Routine</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('index')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Teacher</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Erickson</option>
                                        <option>Mori</option>
                                        <option>Joseph</option>
                                        <option>James</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Class</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Section</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>A</option>
                                        <option>B</option>
                                        <option>C</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Day</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Monday</option>
                                        <option>Tuesday</option>
                                        <option>Wedneshday</option>
                                        <option>Thursday</option>
                                        <option>Friday</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Start Time</label>
                                            <div class="date-pic">
                                                <input type="text" class="form-control timepicker"
                                                    placeholder="Choose">
                                                <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">End Time</label>
                                            <div class="date-pic">
                                                <input type="text" class="form-control timepicker"
                                                    placeholder="Choose">
                                                <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Class Room</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>101</option>
                                        <option>102</option>
                                        <option>103</option>
                                        <option>104</option>
                                        <option>105</option>
                                    </select>
                                </div>
                                <div
                                    class="modal-satus-toggle d-flex align-items-center justify-content-between">
                                    <div class="status-title">
                                        <h5>Status</h5>
                                        <p>Change the Status by toggle </p>
                                    </div>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user1" class="check">
                                        <label for="user1" class="checktoggle"> </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Class Routine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- /Add Class Routine -->

<!-- Add Event -->
<div class="modal fade" id="add_event">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">New Event</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>					
            <form action="{{url('index')}}">
                <div class="modal-body">		
                    <div class="row">
                        <div class="col-md-12">
                            <div>
                                <label class="form-label">Event For</label>
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="all" checked="">
                                        <label class="form-check-label" for="all">
                                            All
                                        </label>
                                    </div>
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="students">
                                        <label class="form-check-label" for="students">
                                            Students
                                        </label>
                                    </div>
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="staffs">
                                        <label class="form-check-label" for="staffs">
                                            Staffs
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="all-content" id="all-student">
                                <div class="mb-3">
                                    <label class="form-label">Classes</label>
                                    <select class="select">
                                        <option>All Classes</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sections</label>
                                    <select class="select">
                                        <option>All Sections</option>
                                        <option>A</option>
                                        <option>B</option>
                                    </select>
                                </div>
                            </div>
                            <div class="all-content" id="all-staffs">
                                <div class="mb-3">
                                    <div class="bg-light-500 p-3 pb-2 rounded">
                                        <label class="form-label">Role</label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Admin
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox" checked>Teacher
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Driver
                                                </div>										
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Accountant
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Librarian
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Receptionist
                                                </div>											
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">All Teachers</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                            </div>
                        </div>			
                        <div class="mb-3">
                            <label class="form-label">Event Title</label>
                            <input type="text" class="form-control" placeholder="Enter Title">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event Category</label>
                            <select class="select">
                                <option>Select</option>
                                <option>Celebration</option>
                                <option>Training</option>
                                <option>Meeting</option>
                                <option>Holidays</option>
                            </select>
                        </div>						
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="15 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>											
                        </div>									
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="21 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>											
                        </div>								
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Time</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control timepicker" placeholder="09:10 AM">
                                    <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                </div>
                            </div>											
                        </div>							
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Time</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control timepicker" placeholder="12:50 PM">
                                    <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                </div>
                            </div>											
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="bg-light p-3 pb-2 rounded">
                                    <div class="mb-3">
                                        <label class="form-label">Attachment</label>
                                        <p>Upload size of 4MB, Accepted Format PDF</p>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap">
                                        <div class="btn btn-primary drag-upload-btn mb-2 me-2">
                                            <i class="ti ti-file-upload me-1"></i>Upload
                                            <input type="file" class="form-control image_sign" multiple="">
                                        </div>
                                        <p class="mb-2">Fees_Structure.pdf</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" rows="4">Meeting with Staffs on the Quality Improvement s and completion of syllabus before the August,  enhance the students health issue</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Event -->	
@endif
@if (Route::is(['layout-dark']))
<!-- Add Class Routine -->
<div class="modal fade" id="add_class_routine">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-wrapper">
                <div class="modal-header">
                    <h4 class="modal-title">Add Class Routine</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('index')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Teacher</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Erickson</option>
                                        <option>Mori</option>
                                        <option>Joseph</option>
                                        <option>James</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Class</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Section</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>A</option>
                                        <option>B</option>
                                        <option>C</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Day</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Monday</option>
                                        <option>Tuesday</option>
                                        <option>Wedneshday</option>
                                        <option>Thursday</option>
                                        <option>Friday</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Start Time</label>
                                            <div class="date-pic">
                                                <input type="text" class="form-control timepicker"
                                                    placeholder="Choose">
                                                <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">End Time</label>
                                            <div class="date-pic">
                                                <input type="text" class="form-control timepicker"
                                                    placeholder="Choose">
                                                <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Class Room</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>101</option>
                                        <option>102</option>
                                        <option>103</option>
                                        <option>104</option>
                                        <option>105</option>
                                    </select>
                                </div>
                                <div
                                    class="modal-satus-toggle d-flex align-items-center justify-content-between">
                                    <div class="status-title">
                                        <h5>Status</h5>
                                        <p>Change the Status by toggle </p>
                                    </div>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user1" class="check">
                                        <label for="user1" class="checktoggle"> </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Class Routine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- /Add Class Routine -->

<!-- Add Event -->
<div class="modal fade" id="add_event">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">New Event</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>					
            <form action="{{url('index')}}">
                <div class="modal-body">		
                    <div class="row">
                        <div class="col-md-12">
                            <div>
                                <label class="form-label">Event For</label>
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="all" checked="">
                                        <label class="form-check-label" for="all">
                                            All
                                        </label>
                                    </div>
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="students">
                                        <label class="form-check-label" for="students">
                                            Students
                                        </label>
                                    </div>
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="staffs">
                                        <label class="form-check-label" for="staffs">
                                            Staffs
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="all-content" id="all-student">
                                <div class="mb-3">
                                    <label class="form-label">Classes</label>
                                    <select class="select">
                                        <option>All Classes</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sections</label>
                                    <select class="select">
                                        <option>All Sections</option>
                                        <option>A</option>
                                        <option>B</option>
                                    </select>
                                </div>
                            </div>
                            <div class="all-content" id="all-staffs">
                                <div class="mb-3">
                                    <div class="bg-light-500 p-3 pb-2 rounded">
                                        <label class="form-label">Role</label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Admin
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox" checked>Teacher
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Driver
                                                </div>										
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Accountant
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Librarian
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Receptionist
                                                </div>											
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">All Teachers</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                            </div>
                        </div>			
                        <div class="mb-3">
                            <label class="form-label">Event Title</label>
                            <input type="text" class="form-control" placeholder="Enter Title">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event Category</label>
                            <select class="select">
                                <option>Select</option>
                                <option>Celebration</option>
                                <option>Training</option>
                                <option>Meeting</option>
                                <option>Holidays</option>
                            </select>
                        </div>						
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="15 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>											
                        </div>									
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="21 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>											
                        </div>								
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Time</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control timepicker" placeholder="09:10 AM">
                                    <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                </div>
                            </div>											
                        </div>							
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Time</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control timepicker" placeholder="12:50 PM">
                                    <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                </div>
                            </div>											
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="bg-light p-3 pb-2 rounded">
                                    <div class="mb-3">
                                        <label class="form-label">Attachment</label>
                                        <p>Upload size of 4MB, Accepted Format PDF</p>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap">
                                        <div class="btn btn-primary drag-upload-btn mb-2 me-2">
                                            <i class="ti ti-file-upload me-1"></i>Upload
                                            <input type="file" class="form-control image_sign" multiple="">
                                        </div>
                                        <p class="mb-2">Fees_Structure.pdf</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" rows="4">Meeting with Staffs on the Quality Improvement s and completion of syllabus before the August,  enhance the students health issue</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Event -->	
@endif
@if (Route::is(['layout-default']))
<!-- Add Class Routine -->
<div class="modal fade" id="add_class_routine">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-wrapper">
                <div class="modal-header">
                    <h4 class="modal-title">Add Class Routine</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('index')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Teacher</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Erickson</option>
                                        <option>Mori</option>
                                        <option>Joseph</option>
                                        <option>James</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Class</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Section</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>A</option>
                                        <option>B</option>
                                        <option>C</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Day</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Monday</option>
                                        <option>Tuesday</option>
                                        <option>Wedneshday</option>
                                        <option>Thursday</option>
                                        <option>Friday</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Start Time</label>
                                            <div class="date-pic">
                                                <input type="text" class="form-control timepicker"
                                                    placeholder="Choose">
                                                <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">End Time</label>
                                            <div class="date-pic">
                                                <input type="text" class="form-control timepicker"
                                                    placeholder="Choose">
                                                <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Class Room</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>101</option>
                                        <option>102</option>
                                        <option>103</option>
                                        <option>104</option>
                                        <option>105</option>
                                    </select>
                                </div>
                                <div
                                    class="modal-satus-toggle d-flex align-items-center justify-content-between">
                                    <div class="status-title">
                                        <h5>Status</h5>
                                        <p>Change the Status by toggle </p>
                                    </div>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user1" class="check">
                                        <label for="user1" class="checktoggle"> </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Class Routine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- /Add Class Routine -->

<!-- Add Event -->
<div class="modal fade" id="add_event">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">New Event</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>					
            <form action="{{url('index')}}">
                <div class="modal-body">		
                    <div class="row">
                        <div class="col-md-12">
                            <div>
                                <label class="form-label">Event For</label>
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="all" checked="">
                                        <label class="form-check-label" for="all">
                                            All
                                        </label>
                                    </div>
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="students">
                                        <label class="form-check-label" for="students">
                                            Students
                                        </label>
                                    </div>
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="staffs">
                                        <label class="form-check-label" for="staffs">
                                            Staffs
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="all-content" id="all-student">
                                <div class="mb-3">
                                    <label class="form-label">Classes</label>
                                    <select class="select">
                                        <option>All Classes</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sections</label>
                                    <select class="select">
                                        <option>All Sections</option>
                                        <option>A</option>
                                        <option>B</option>
                                    </select>
                                </div>
                            </div>
                            <div class="all-content" id="all-staffs">
                                <div class="mb-3">
                                    <div class="bg-light-500 p-3 pb-2 rounded">
                                        <label class="form-label">Role</label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Admin
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox" checked>Teacher
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Driver
                                                </div>										
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Accountant
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Librarian
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Receptionist
                                                </div>											
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">All Teachers</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                            </div>
                        </div>			
                        <div class="mb-3">
                            <label class="form-label">Event Title</label>
                            <input type="text" class="form-control" placeholder="Enter Title">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event Category</label>
                            <select class="select">
                                <option>Select</option>
                                <option>Celebration</option>
                                <option>Training</option>
                                <option>Meeting</option>
                                <option>Holidays</option>
                            </select>
                        </div>						
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="15 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>											
                        </div>									
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="21 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>											
                        </div>								
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Time</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control timepicker" placeholder="09:10 AM">
                                    <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                </div>
                            </div>											
                        </div>							
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Time</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control timepicker" placeholder="12:50 PM">
                                    <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                </div>
                            </div>											
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="bg-light p-3 pb-2 rounded">
                                    <div class="mb-3">
                                        <label class="form-label">Attachment</label>
                                        <p>Upload size of 4MB, Accepted Format PDF</p>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap">
                                        <div class="btn btn-primary drag-upload-btn mb-2 me-2">
                                            <i class="ti ti-file-upload me-1"></i>Upload
                                            <input type="file" class="form-control image_sign" multiple="">
                                        </div>
                                        <p class="mb-2">Fees_Structure.pdf</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" rows="4">Meeting with Staffs on the Quality Improvement s and completion of syllabus before the August,  enhance the students health issue</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Event -->	
@endif
@if (Route::is(['delete-account']))
<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('delete-account')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->
@endif
@if (Route::is(['appointments-calendar']))
<!-- Add New Appointment -->
<div class="modal fade" id="add_appointment">
    <div class="modal-dialog modal-dialog-centered custom-modal">
        <div class="modal-content">
            <div class="modal-wrapper">
                <div class="modal-header custom-modal-header">
                    <div class="page-title">
                        <h4>New Appointment</h4>
                    </div>							
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span class="modal-close-btn"><i class="ti ti-x"></i></span>
                    </button>
                </div>
                <form action="{{url('appointments-calendar')}}">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="col-form-label">Select Patient</label>
                                <select class="image-select">
                                    <option data-image="build/img/patients/patient-01.jpg">Gifford</option>
                                    <option data-image="build/img/patients/patient-02.jpg">Richard</option>
                                    <option data-image="build/img/patients/patient-03.jpg">Louella</option>
                                    <option data-image="build/img/patients/patient-04.jpg">Thomas</option>
                                    <option data-image="build/img/patients/patient-05.jpg">Shaver</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="col-form-label">Patient Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Out patient</option>
                                    <option>In patient</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="col-form-label">Select Department</label>
                                <select class="select">
                                    <option>Anaesthesiology</option>
                                    <option>Dental Surgery</option>
                                    <option>ENT Surgery</option>
                                    <option>Dermatology</option>
                                    <option>Ophthalmology</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="col-form-label">Select Doctor</label>
                                <select class="image-select">
                                    <option data-image="build/img/doctors/doctor-01.jpg">Dr. Cynthia</option>
                                    <option data-image="build/img/doctors/doctor-02.jpg">Dr. Evans</option>
                                    <option data-image="build/img/doctors/doctor-03.jpg">Dr. Laird</option>
                                    <option data-image="build/img/doctors/doctor-04.jpg">Dr. Sara</option>
                                    <option data-image="build/img/doctors/doctor-05.jpg">Dr. Corine</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-4">
                                <label class="col-form-label">Preffered  Mode of Consultation</label>
                                <div class="row gx-3">
                                    <div class="col-sm-3">
                                        <label class="payment-mode">
                                            <input type="radio" name="consult">
                                            <span class="payment-check">
                                                In-person
                                            </span>							
                                        </label>
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="payment-mode">
                                            <input type="radio" name="consult" checked="">
                                            <span class="payment-check">
                                                Chat
                                            </span>							
                                        </label>
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="payment-mode">
                                            <input type="radio" name="consult">
                                            <span class="payment-check">
                                                Video Call
                                            </span>							
                                        </label>
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="payment-mode mb-0">
                                            <input type="radio" name="consult">
                                            <span class="payment-check">
                                                Audio Call
                                            </span>							
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="col-form-label">Date</label>
                                <input type="text" class="form-control datetimepicker" placeholder="--/--/----">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="col-form-label">Start Time</label>
                                <input type="text" class="form-control timepicker" placeholder="--/--/----">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="col-form-label">End Time</label>
                                <input type="text" class="form-control timepicker" placeholder="--/--/----">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    <label class="col-form-label">Reason</label>
                                    <i class="ti ti-layout-board-split text-primary"></i>
                                </div>
                                <div class="input-form-tags modal-tags">
                                    <div class="input-clear-text">
                                        <input class="input-tags form-control" id="inputBox" type="text" data-role="tagsinput" placeholder="+ Add new Reason"  name="Label" value="Cold, Cough, Fever" >
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="col-form-label">Quick Notes</label>
                                <textarea class="form-control" placeholder="Additional Information" rows="4"></textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-4">
                                <label class="col-form-label">Mode of Payment</label>
                                <div class="row gx-3">
                                    <div class="col-sm-6">
                                        <label class="payment-mode">
                                            <input type="radio" name="gender" checked="">
                                            <span class="payment-check">
                                                Paytm
                                            </span>							
                                        </label>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="payment-mode mb-0">
                                            <input type="radio" name="gender" checked="">
                                            <span class="payment-check">
                                                Cash
                                            </span>							
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="dlete-modal-btn text-end">
                            <a href="#" class="btn btn-outline-primary me-2" data-bs-dismiss="modal">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Appointment</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- /Add New Appointment -->		

<!-- Clinic List -->
<div class="modal fade" id="clinic-list">
    <div class="modal-dialog modal-dialog-centered custom-modal modal-md">
        <div class="modal-content">
            <div class="modal-body">							
                <div class="clinic-list">
                    <h5>Select Clinic</h5>
                    <div class="form-check active">
                        <input class="form-check-input" type="radio" name="clinic" id="clinic1" checked>
                        <label class="form-check-label" for="clinic1">
                            <span class="clinic-icon"><img src="{{URL::asset('build/img/icons/clinic-02.svg')}}" alt="icon"></span>
                            Arive Clinic
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="clinic" id="clinic2">
                        <label class="form-check-label" for="clinic2">
                            <span class="clinic-icon"><img src="{{URL::asset('build/img/icons/clinic-03.svg')}}" alt="icon"></span>
                            Paradise Clinic
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="clinic" id="clinic3">
                        <label class="form-check-label" for="clinic3">
                            <span class="clinic-icon"><img src="{{URL::asset('build/img/icons/clinic-04.svg')}}" alt="icon"></span>
                            Hope Garden Clinic
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="clinic" id="clinic4">
                        <label class="form-check-label" for="clinic4">
                            <span class="clinic-icon"><img src="{{URL::asset('build/img/icons/clinic-05.svg')}}" alt="icon"></span>
                            Maple Clinic
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="clinic" id="clinic5">
                        <label class="form-check-label" for="clinic5">
                            <span class="clinic-icon"><img src="{{URL::asset('build/img/icons/clinic-06.svg')}}" alt="icon"></span>
                            Swanlake Clinic
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Clinic List -->


@endif
@if (Route::is(['call-history']))
	<!-- details popup -->
	<div class="modal fade" id="user-profile-new">
		<div class="modal-dialog modal-dialog-centered history-modal-profile">
			<div class="modal-content">
				<div class="modal-body">
					<div class="text-center right-sidebar-profile mb-3">
						<figure class="avatar avatar-xxxl">
							<img src="{{URL::asset('build/img/users/user-23.jpg')}}" alt="image">
						</figure>
						<div class="chat-options chat-option-profile">
							<ul class="list-inline">
								<li class="list-inline-item">
									<a href="{{url('call')}}" class="btn btn-outline-light rounded-circle btn-icon"
										data-bs-toggle="tooltip" data-bs-placement="bottom" title=""
										data-bs-original-title="Voice Call">
										<i class="bx bx-phone"></i>
									</a>
								</li>
								<li class="list-inline-item">
									<a href="{{url('chat')}}" class="btn btn-outline-light rounded-circle btn-icon"
										data-bs-toggle="tooltip" data-bs-placement="bottom" title=""
										data-bs-original-title="Chat">
										<i class="bx bx-message-square-dots"></i>
									</a>
								</li>
								<li class="list-inline-item ">
									<a href="{{url('video-call')}}"
										class="btn btn-outline-light profile-open rounded-circle btn-icon"
										data-bs-toggle="tooltip" data-bs-placement="bottom" title=""
										data-bs-original-title="Video Call">
										<i class="bx bx-video"></i>
									</a>
								</li>
							</ul>
						</div>
					</div>
					<div class="bg-light-300 p-3 pb-1 rounded">
						<div class="row">
							<div class="col-lg-6">
								<div class="modal-userlist">
									<ul>
										<li class="d-flex flex-column text-dark fw-medium mb-2">Name<span
												class="text-default fw-normal">Thomas</span></li>
										<li class="d-flex flex-column text-dark fw-medium mb-2">Phone<span
												class="text-default fw-normal">+1 25182 94528</span></li>
										<li class="d-flex flex-column text-dark fw-medium mb-2">Email<span
												class="text-default fw-normal">thomas@example.com</span></li>
									</ul>
								</div>
							</div>
							<div class="col-lg-6">
								<div class="">
									<ul>
										<li class="d-flex flex-column text-dark fw-medium mb-2">Total Calls<span
												class="text-default fw-normal">20</span></li>
										<li class="d-flex flex-column text-dark fw-medium mb-2">Average Call Timing<span
												class="text-default fw-normal">0.30</span></li>
										<li class="d-flex flex-column text-dark fw-medium mb-2">Average Waiting
											Time<span class="text-default fw-normal">00.5</span></li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- /details popup -->
@endif
@if (Route::is(['permission']))
<!-- Add Role -->
<div class="modal fade" id="add_role">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Role</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('roles-permission')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-0">
                                <label class="form-label">Role Name</label>
                                <input type="text" class="form-control" placeholder="Enter State Name">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Role</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Role -->

@endif
@if (Route::is(['players']))
	<!-- Add Player -->
    <div class="modal fade" id="add_players">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Player</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('players')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Player Name</label>
                                    <input type="text" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sports</label>
                                    <input type="text" class="form-control">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Date of Join</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Player</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add Player -->

    <!-- Edit Player -->
    <div class="modal fade" id="edit_players">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Player</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('players')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Player Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Player Name"
                                        value="Francis">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sports</label>
                                    <input type="text" class="form-control" placeholder="Enter Sports"
                                        value="Cricket">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Date of Join</label>
                                    <input type="text" class="form-control" placeholder="Enter Date of Join"
                                        value="25 Apr 2024">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Edit Player -->

    <!-- Delete Modal -->
    <div class="modal fade" id="delete-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{url('players')}}">
                    <div class="modal-body text-center">
                        <span class="delete-icon">
                            <i class="ti ti-trash-x"></i>
                        </span>
                        <h4>Confirm Deletion</h4>
                        <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                        <div class="d-flex justify-content-center">
                            <a href="javascript:void(0);" class="btn btn-light me-3"
                                data-bs-dismiss="modal">Cancel</a>
                            <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Delete Modal -->


@endif
@if (Route::is(['staff-details']))
<!-- Login Details -->
<div class="modal fade" id="login_detail">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Login Details</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="student-detail-info">
                    <span class="student-img"><img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" alt="img"></span>
                    <div class="name-info">
                        <h6>Teresa <span>III, A</span></h6>
                    </div>
                </div>
                <div class="table-responsive custom-table no-datatable_length">
                    <table class="table datanew">
                        <thead class="thead-light">
                            <tr>
                                <th>User Type</th>
                                <th>User Name</th>
                                <th>Password </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Teacher</td>
                                <td>teacher20</td>
                                <td>teacher@53</td>
                            </tr>
                            <tr>
                                <td>Parent</td>
                                <td>parent53</td>
                                <td>parent@53</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>
<!-- /Login Details -->
@endif

@if (Route::is(['staff-leaves']))
<!-- Apply Leave -->
<div class="modal fade" id="apply_leave">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Apply Leave</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form action="{{url('staff-leaves')}}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-4">
                                <label class="form-label">Leave Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker"
                                        placeholder="15 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Leave Type</label>
                                <select class="select">
                                    <option>Select</option>
                                    <option>Medical Leave</option>
                                    <option>Casual Leave</option>
                                    <option>Special Leave</option>
                                    <option>Maternity Leave</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Leave From date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker"
                                        placeholder="15 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Leave to Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker"
                                        placeholder="15 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Leave Days</label>
                                <div class="d-flex align-items-center check-radio-group">
                                    <label class="custom-radio">
                                        <input type="radio" name="radio" checked="">
                                        <span class="checkmark"></span>
                                        Full day
                                    </label>
                                    <label class="custom-radio">
                                        <input type="radio" name="radio">
                                        <span class="checkmark"></span>
                                        First Half
                                    </label>
                                    <label class="custom-radio">
                                        <input type="radio" name="radio">
                                        <span class="checkmark"></span>
                                        Second Half
                                    </label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">No of Days</label>
                                <input type="text" class="form-control">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Reason</label>
                                <input type="text" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Apply Leave</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Apply Leave -->
	
@endif
@if (Route::is(['staffs-attendance']))
<!-- Apply Leave -->
<div class="modal fade" id="apply_leave">
    <div class="modal-dialog modal-dialog-centered custom-modal">
        <div class="modal-content">
            <div class="modal-wrapper">
                <div class="modal-header">
                    <h4 class="modal-title">Apply Leave</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>					
                <form action="{{url('staff-leaves')}}">
                    <div class="modal-card">		
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-4">
                                    <label class="col-form-label">Leave Date</label>
                                    <div class="date-pic">
                                        <input type="text" class="form-control datetimepicker" placeholder="15 May 2024">
                                        <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="col-form-label">Leave Type</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Medical Leave</option>
                                        <option>Casual Leave</option>
                                        <option>Special Leave</option>
                                        <option>Maternity Leave</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="col-form-label">Leave From date</label>
                                    <div class="date-pic">
                                        <input type="text" class="form-control datetimepicker" placeholder="15 May 2024">
                                        <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="col-form-label">Leave to Date</label>
                                    <div class="date-pic">
                                        <input type="text" class="form-control datetimepicker" placeholder="15 May 2024">
                                        <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="col-form-label">Leave Days</label>
                                    <div class="d-flex align-items-center check-radio-group">
                                        <label class="custom-radio">
                                            <input type="radio" name="radio" checked="">
                                            <span class="checkmark"></span>
                                            Full day
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="radio">
                                            <span class="checkmark"></span>
                                            First Half
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="radio">
                                            <span class="checkmark"></span>
                                            Second Half
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="col-form-label">No of Days</label>
                                    <input type="text" class="form-control">
                                </div>
                                <div class="mb-0">
                                    <label class="col-form-label">Reason</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-btn text-end">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Apply Leave</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- /Apply Leave -->	

<!-- Delete Modal -->	
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered custom-modal promote-modal">
        <div class="modal-content">
            <div class="modal-wrapper">					
                <form action="{{url('staff-leaves')}}">
                    <div class="promote-modal-body text-center">
                        <span class="delete-icon">
                            <i class="ti ti-trash-x"></i>
                        </span>
                        <h4>Confirm Deletion</h4>
                        <p>You want to delete all the marked items, this can't be undone once you delete.</p>
                        <div class="d-flex justify-content-center">
                            <a href="javascript:void(0);" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</a>
                            <button type="submit" class="btn btn-danger" id="toprightToastBtn" data-bs-dismiss="modal">Yes, Delete</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- /Delete Modal -->	


@endif
@if (Route::is(['pages']))
<!-- Delete Modal -->
<div class="modal fade" id="delete-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{url('pages')}}">
                <div class="modal-body text-center">
                    <span class="delete-icon">
                        <i class="ti ti-trash-x"></i>
                    </span>
                    <h4>Confirm Deletion</h4>
                    <p>You want to delete all the marked items, this cant be undone once you delete.</p>
                    <div class="d-flex justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-light me-3"
                            data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Delete Modal -->
@endif
@if (Route::is(['layout-rtl']))
<!-- Add Class Routine -->
<div class="modal fade" id="add_class_routine">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-wrapper">
                <div class="modal-header">
                    <h4 class="modal-title">Add Class Routine</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <form action="{{url('index')}}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Teacher</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Erickson</option>
                                        <option>Mori</option>
                                        <option>Joseph</option>
                                        <option>James</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Class</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Section</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>A</option>
                                        <option>B</option>
                                        <option>C</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Day</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>Monday</option>
                                        <option>Tuesday</option>
                                        <option>Wedneshday</option>
                                        <option>Thursday</option>
                                        <option>Friday</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Start Time</label>
                                            <div class="date-pic">
                                                <input type="text" class="form-control timepicker"
                                                    placeholder="Choose">
                                                <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">End Time</label>
                                            <div class="date-pic">
                                                <input type="text" class="form-control timepicker"
                                                    placeholder="Choose">
                                                <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Class Room</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>101</option>
                                        <option>102</option>
                                        <option>103</option>
                                        <option>104</option>
                                        <option>105</option>
                                    </select>
                                </div>
                                <div
                                    class="modal-satus-toggle d-flex align-items-center justify-content-between">
                                    <div class="status-title">
                                        <h5>Status</h5>
                                        <p>Change the Status by toggle </p>
                                    </div>
                                    <div class="status-toggle modal-status">
                                        <input type="checkbox" id="user1" class="check">
                                        <label for="user1" class="checktoggle"> </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Class Routine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- /Add Class Routine -->

<!-- Add Event -->
<div class="modal fade" id="add_event">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">New Event</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>					
            <form action="{{url('index')}}">
                <div class="modal-body">		
                    <div class="row">
                        <div class="col-md-12">
                            <div>
                                <label class="form-label">Event For</label>
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="all" checked="">
                                        <label class="form-check-label" for="all">
                                            All
                                        </label>
                                    </div>
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="students">
                                        <label class="form-check-label" for="students">
                                            Students
                                        </label>
                                    </div>
                                    <div class="form-check me-3 mb-3">
                                        <input class="form-check-input" type="radio" name="event" id="staffs">
                                        <label class="form-check-label" for="staffs">
                                            Staffs
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="all-content" id="all-student">
                                <div class="mb-3">
                                    <label class="form-label">Classes</label>
                                    <select class="select">
                                        <option>All Classes</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sections</label>
                                    <select class="select">
                                        <option>All Sections</option>
                                        <option>A</option>
                                        <option>B</option>
                                    </select>
                                </div>
                            </div>
                            <div class="all-content" id="all-staffs">
                                <div class="mb-3">
                                    <div class="bg-light-500 p-3 pb-2 rounded">
                                        <label class="form-label">Role</label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Admin
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox" checked>Teacher
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Driver
                                                </div>										
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Accountant
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Librarian
                                                </div>
                                                <div class="form-check form-check-sm mb-2">
                                                    <input class="form-check-input" type="checkbox">Receptionist
                                                </div>											
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">All Teachers</label>
                                    <select class="select">
                                        <option>Select</option>
                                        <option>I</option>
                                        <option>II</option>
                                        <option>III</option>
                                        <option>IV</option>
                                    </select>
                                </div>
                            </div>
                        </div>			
                        <div class="mb-3">
                            <label class="form-label">Event Title</label>
                            <input type="text" class="form-control" placeholder="Enter Title">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event Category</label>
                            <select class="select">
                                <option>Select</option>
                                <option>Celebration</option>
                                <option>Training</option>
                                <option>Meeting</option>
                                <option>Holidays</option>
                            </select>
                        </div>						
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="15 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>											
                        </div>									
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control datetimepicker" placeholder="21 May 2024">
                                    <span class="cal-icon"><i class="ti ti-calendar"></i></span>
                                </div>
                            </div>											
                        </div>								
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Time</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control timepicker" placeholder="09:10 AM">
                                    <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                </div>
                            </div>											
                        </div>							
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Time</label>
                                <div class="date-pic">
                                    <input type="text" class="form-control timepicker" placeholder="12:50 PM">
                                    <span class="cal-icon"><i class="ti ti-clock"></i></span>
                                </div>
                            </div>											
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="bg-light p-3 pb-2 rounded">
                                    <div class="mb-3">
                                        <label class="form-label">Attachment</label>
                                        <p>Upload size of 4MB, Accepted Format PDF</p>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap">
                                        <div class="btn btn-primary drag-upload-btn mb-2 me-2">
                                            <i class="ti ti-file-upload me-1"></i>Upload
                                            <input type="file" class="form-control image_sign" multiple="">
                                        </div>
                                        <p class="mb-2">Fees_Structure.pdf</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" rows="4">Meeting with Staffs on the Quality Improvement s and completion of syllabus before the August,  enhance the students health issue</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Add Event -->	
@endif