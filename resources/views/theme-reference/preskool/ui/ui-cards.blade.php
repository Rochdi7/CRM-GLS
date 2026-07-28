<?php $page = 'ui-cards'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
       Cards
        @endslot
    @endcomponent	
        
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Card Default</h5>
                    </div>
                    <div class="card-body">
                        <img class="card-img-top pb-2 rounded-0" src="{{URL::asset('build/img/media/img-8.jpg')}}" alt="Card image cap">
                        <h5 class="card-title mb-2">Card title</h5>
                        <p class="card-text">Some quick example text to build on the card..</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">With Link</h5>
                    </div>
                    <div class="card-body">
                        <img class="card-img-top pb-2 rounded-0" src="{{URL::asset('build/img/media/img-8.jpg')}}" alt="Card image cap">
                        <h5 class="card-title mb-2">Card title</h5>
                        <p class="card-text">Some quick example text to build on the card..</p>
                        <a href="#" class="card-link text-decoration-underline text-primary">View More</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">With Link</h5>
                    </div>
                    <div class="card-body">
                        <img class="card-img-top pb-2 rounded-0" src="{{URL::asset('build/img/media/img-8.jpg')}}" alt="Card image cap">
                        <h5 class="card-title mb-2">Card title</h5>
                        <p class="card-text">Some quick example text to build on the card..</p>
                        <a href="#" class="btn btn-primary">Go Somewhere</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Card title</h5>
                                        <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                        <a href="#" class="card-link text-decoration-underline text-primary">Card link</a>
                                        <a href="#" class="card-link text-decoration-underline text-primary">Another link</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card mb-0">
                                    <div class="card-body">
                                        <h5 class="card-title">Card title</h5>
                                        <h6 class="card-subtitle mb-2 text-muted">Card subtitle</h6>
                                        <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                        <a href="#" class="card-link text-decoration-underline text-primary">Card link</a>
                                        <a href="#" class="card-link text-decoration-underline text-primary">Another link</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Header</h5>
                    </div>
                    <div class="card-body">
                        <div class="card mb-0">
                            <div class="card-header bg-light">
                                <h5 class="card-title">Card Header</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="mb-2">Special title treatment</h6>
                                <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Footer</h5>
                    </div>
                    <div class="card-body">
                        <div class="card mb-0">
                            <div class="card-body">
                                <h6 class="mb-2">Special title treatment</h6>
                                <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
                            </div>
                            <div class="card-footer bg-light">
                                <h5>Card Footer</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Header and footer</h5>
                    </div>
                    <div class="card-body">
                        <div class="card mb-0">
                            <div class="card-header bg-light">
                                <h5 class="card-title">Card Header</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="mb-2">Special title treatment</h6>
                                <p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
                            </div>
                            <div class="card-footer bg-light">
                                <h5>Card Footer</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Horizontal Right Align Content</h5>
                    </div>
                    <div class="card-body">
                        <div class="card mb-0">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img src="{{URL::asset('build/img/media/img-9.jpg')}}" class="img-fluid rounded-start h-100" alt="...">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title">Card title</h5>
                                        <p class="card-text mb-1">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
                                        <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Horizontal Right Align Content</h5>
                    </div>
                    <div class="card-body">
                        <div class="card mb-0">
                            <div class="row g-0">
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title">Card title</h5>
                                        <p class="card-text mb-1">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
                                        <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <img src="{{URL::asset('build/img/media/img-9.jpg')}}" class="img-fluid rounded-end h-100" alt="...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Card styles</h5>
                    </div>
                    <div class="card-body card-header-border pb-0">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="card text-white bg-primary">
                                    <div class="card-header">
                                        <h5 class="card-title text-white">Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-white">Special title treatment</h5>
                                        <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card text-white bg-secondary">
                                    <div class="card-header">
                                        <h5 class="card-title text-white">Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-white">Special title treatment</h5>
                                        <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card text-white bg-dark">
                                    <div class="card-header">
                                        <h5 class="card-title text-white">Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-white">Special title treatment</h5>
                                        <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card text-white bg-success">
                                    <div class="card-header">
                                        <h5 class="card-title text-white">Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-white">Special title treatment</h5>
                                        <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card text-white bg-warning">
                                    <div class="card-header">
                                        <h5 class="card-title text-white">Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-white">Special title treatment</h5>
                                        <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card text-white bg-danger">
                                    <div class="card-header">
                                        <h5 class="card-title text-white">Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-white">Special title treatment</h5>
                                        <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card text-white bg-info">
                                    <div class="card-header">
                                        <h5 class="card-title text-white">Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-white">Special title treatment</h5>
                                        <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card text-white bg-pink">
                                    <div class="card-header">
                                        <h5 class="card-title text-white">Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-white">Special title treatment</h5>
                                        <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Card Border</h5>
                    </div>
                    <div class="card-body card-header-border pb-0">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="card border-primary">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title">Card Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-primary">Primary card title</h5>
                                        <p class="card-text text-primary">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card border-secondary">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title">Card Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-secondary">Secondary card title</h5>
                                        <p class="card-text text-secondary">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card border-success">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title">Card Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-success">Success card title</h5>
                                        <p class="card-text text-success">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card border-danger">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title">Card Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-danger">Danger card title</h5>
                                        <p class="card-text text-danger">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card border-warning">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title">Card Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-warning">Warning card title</h5>
                                        <p class="card-text text-warning">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card border-info">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title">Card Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-info">Info card title</h5>
                                        <p class="card-text text-info">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title">Card Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">Light card title</h5>
                                        <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <div class="card border-dark">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title">Card Header</h5>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-dark">Dark card title</h5>
                                        <p class="card-text text-dark">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                                    </div>
                                </div>
                            </div>

                            
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Card Groups</h5>
                    </div>
                    <div class="card-body pb-0 card-header-border">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-group">
                                    <div class="card">
                                        <img src="{{URL::asset('build/img/media/img-10.jpg')}}" class="card-img-top" alt="...">
                                        <div class="card-body">
                                            <h5 class="card-title">Card title</h5>
                                            <p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
                                            <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <img src="{{URL::asset('build/img/media/img-10.jpg')}}" class="card-img-top" alt="...">
                                        <div class="card-body">
                                            <h5 class="card-title">Card title</h5>
                                            <p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
                                            <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <img src="{{URL::asset('build/img/media/img-10.jpg')}}" class="card-img-top" alt="...">
                                        <div class="card-body">
                                            <h5 class="card-title">Card title</h5>
                                            <p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
                                            <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
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
@endsection
