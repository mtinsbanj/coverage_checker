<div>
  <x-loader />
  <div class="intro">
    <img src="img/coverage_bg.png" alt="" class="cover_img">
    <div class="mask d-flex align-items-center h-100">
      <div class="container main-content">
        <div class="row justify-content-center">
          <div class="col-md-12 logo">
            <img src="img/logo.png" class="img-responsive" alt="fob logo">
          </div>
          <div class="col-xl-7 col-md-8">
            <form class="bg-white rounded-5 shadow-5-strong px-5 pb-5 pt-3">
              <!-- Session Messages -->
              <div>
                @if (session()->has('thankyou_message'))
                  <div class="alert alert-primary" role="alert">
                    {{ session('thankyou_message') }}
                  </div>
                  <hr />
                @endif

                @if (session()->has('message'))
                  <div class="alert alert-primary" role="alert">
                    {{ session('message') }}
                  </div>
                @endif

                @if (session()->has('success_message'))
                  <div class="gif_success">
                    <img src="img/success_animation.gif" class="img-responsive">
                  </div>
                  <div class="alert alert-success" role="alert">
                    {!! session('success_message') !!}
                  </div>
                @endif

                @if (session()->has('survey_message'))
                  <div class="alert alert-warning" role="alert">
                    {!! session('survey_message') !!}
                  </div>
                @endif

                @if (session()->has('failed_message'))
                  <div class="alert alert-danger" role="alert">
                    {{ session('failed_message') }}
                  </div>
                @endif
              </div>

              <!-- Address Input -->
              @if(!$isSubmitted)
              <div class="form-outline mb-4">
                <label class="form-label">Enter Address</label>
                <input type="text" wire:model.debounce.150ms="address" 
                       class="form-control" placeholder="Insert full address" />
              </div>
              @endif

              <!-- Location Selection -->
              @if($isLocated)
              <div class="form-outline mb-4">
                <label class="form-label">Choose Your Specific Location</label>
                <select wire:model="selectedaddress" class="form-control">
                  <option value="">---SELECT LOCATION---</option>
                  @foreach($geoAddress as $add)
                  <option value="{{ $add['geometry']['location']['lat'].'-'.$add['geometry']['location']['lng'] }}">
                    {{ $add['formatted_address'] }}
                  </option>
                  @endforeach
                </select>
              </div>
              @endif

              <!-- Action Buttons -->
              <div class="button-group">
                @if(!$isLocated)
                <button wire:click="convertAddress" type="button" 
                        class="btn btn-primary btn-block">Continue</button>
                @endif

                @if($isLocated)
                  @if (!session()->has('success_message'))
                  <button wire:click="location" type="button" 
                          class="btn btn-primary btn-block">Confirm Coverage</button>
                  @endif

                  @if (session()->has('success_message'))
                  <a href="https://fob.ng/buy-now-lagos" type="button" 
                     class="btn btn-primary btn-block" target="_blank">Buy Now</a>
                  @endif
                @endif
              </div>

              <!-- Additional Options -->
              <div class="row form_details">
                @if (session()->has('message') || session()->has('success_message') || 
                     session()->has('survey_message') || session()->has('failed_message'))
                <div class="col-md-9 mt-3">
                  <div class="left_details">
                    <button type="button" data-toggle="modal" 
                            data-target="#userInfoModal">Let Us Contact You</button>
                  </div>
                </div>
                @endif
                
                @if($isLocated)
                <div class="col-md-3 mt-3">
                  <div class="right_details">
                    <button wire:click="refresh" type="button">
                      <i class="fa fa-refresh"></i> Restart
                    </button>
                  </div>
                </div>
                @endif
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Contact Modal -->
  <div class="modal fade" id="userInfoModal" tabindex="-1" role="dialog" 
       aria-labelledby="userInfoModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Enter Your Details</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form wire:submit.prevent="storeContact">
          <div class="modal-body">
            @if($errorMessage != '')
            <div class="alert alert-danger" role="alert">
              {{ $errorMessage }}
            </div>
            @endif
            
            <div class="form-row">
              <!-- Form fields remain the same -->
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>