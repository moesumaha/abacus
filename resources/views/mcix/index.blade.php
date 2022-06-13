@extends('layout.master')
@section('content')
    <div class="">
        <section >
        <div class="container-fluid">
            
            <!-- /.card -->
            <!-- START ALERTS AND CALLOUTS -->
            <h5 class="mt-4 mb-2"></h5>

            <div class="row">
            <div class="col-md-5">
                
                <div class="card" card-default>
                <div class="card-header">
                    <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    MCIX Check Form
                    </h3>
                </div>
                <div class="card-body">
                    <p class="login-box-msg"></p>
                    <!-- <div class="alert_place">
                    <p style="color: green;">Total: <span id="total_count">7</span></p>
                    <p style="color: green;">Success: <span id="success_count">0</span></p>
                    <p style="color: red;">Error: <span id="error_count">0</span></p>
                    </div> -->
                    <form action="{{url('/check_mcix')}}" id="validateForm" method="POST" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    
                    <div class="form-group">
                        <label for="exampleInputEmail1">Upload Excel</label>
                        <div class="input-group mb-3">
                            <input class="form-control" name="file" type="file" id="file"  >
                        </div>
                    </div>
                    <div class="row">
                        <!-- /.col -->
                        <!-- <div class="col-4"> -->
                        <button type="submit" class="btn btn-primary btn-block submit" >Check MCIX</button>
                        <!-- </div> -->
                        <!-- /.col -->
                    </div>
                    </form>
            
                </div>
                <!-- /.login-card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
            
            <!-- /.col -->
            </div>
            <!-- /.row -->
            <!-- END TYPOGRAPHY -->
        </div><!-- /.container-fluid -->
        </section>
    </div>
@endsection