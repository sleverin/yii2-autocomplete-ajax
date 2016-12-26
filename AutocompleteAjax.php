<?php

namespace keygenqt\autocompleteAjax;

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\InputWidget;

class AutocompleteAjax extends InputWidget
{
    public $startQuery = true;
    public $multiple = false;
    public $url = [];
    public $options = [];
    public $afterSelect = 'function(event, ui) {}';

    private $_baseUrl;
    private $_ajaxUrl;

    public function registerActiveAssets()
    {
        if ($this->_baseUrl === null) {
            $this->_baseUrl = ActiveAssets::register($this->getView())->baseUrl;
        }
        return $this->_baseUrl;
    }

    public function getUrl()
    {
        if ($this->_ajaxUrl === null) {
            $this->_ajaxUrl = Url::toRoute($this->url);
        }
        return $this->_ajaxUrl;
    }

    public function run()
    {
        $id = $this->getId();
        $this->afterSelect = "var afterSelect{$id} = " . $this->afterSelect;
        $value = $this->model->{$this->attribute};
        $this->registerActiveAssets();

        $this->getView()->registerJs("{$this->afterSelect}");

        if ($this->multiple) {
            
            $this->getView()->registerJs("
                
                $('#{$id}').keyup(function(event) {
                    if (event.keyCode == 8 && !$('#{$id}').val().length) {
                        
                        $('#{$id}-hidden').val('');
                        $('#{$id}-hidden').change();
                        
                    } else if ($('.ui-autocomplete').css('display') == 'none' && 
                        $('#{$id}-hidden').val().split(', ').length > $(this).val().split(', ').length) {
                            
                        var val = $('#{$id}').val().split(', ');
                        var ids = [];
                        for (var i = 0; i<val.length; i++) {
                            val[i] = val[i].replace(',', '').trim();
                            ids[i] = cache_{$id}_1[val[i]];
                        }
                        $('#{$id}-hidden').val(ids.join(', '));
                        $('#{$id}-hidden').change();
                    }
                });
                
                $('#{$id}').keydown(function(event) {
                    
                    if (event.keyCode == 13 && $('.ui-autocomplete').css('display') == 'none') {
                        submit_{$id} = $('#{$id}').closest('.grid-view');
                        $('#{$id}').closest('.grid-view').yiiGridView('applyFilter');
                    }
                    
                    if (event.keyCode == 13) {
                        $('.ui-autocomplete').hide();
                    }
                    
                });
                
                $('body').on('beforeFilter', '#' + $('#{$id}').closest('.grid-view').attr('id') , function(event) {
                    return submit_{$id};
                });

                var submit_{$id} = false;
                var cache_{$id} = {};
                var cache_{$id}_1 = {};
                var cache_{$id}_2 = {};
                jQuery('#{$id}').autocomplete(
                {
                    minLength: 1,
                    source: function( request, response )
                    {
                        var term = request.term;

                        if (term in cache_{$id}) {
                            response( cache_{$id}[term]);
                            return;
                        }
                        $.getJSON('{$this->getUrl()}', request, function( data, status, xhr ) {
                            cache_{$id} [term] = data;
                                
                            for (var i = 0; i<data.length; i++) {
                                if (!(data[i].id in cache_{$id}_2)) {
                                    cache_{$id}_1[data[i].label] = data[i].id;
                                    cache_{$id}_2[data[i].id] = data[i].label;
                                }
                            }

                            response(data);
                        });
                    },
                    select: function(event, ui)
                    {
                        var val = $('#{$id}-hidden').val().split(', ');

                        if (val[0] == '') {
                            val[0] = ui.item.id;
                        } else {
                            val[val.length] = ui.item.id;
                        }

                        $('#{$id}-hidden').val(val.join(', '));
                        $('#{$id}-hidden').change();

                        var names = [];
                        for (var i = 0; i<val.length; i++) {
                            names[i] = cache_{$id}_2[val[i]];
                        }

                        setTimeout(function() {
                            $('#{$id}').val(names.join(', '));
                        }, 0);
                    }
                });
            ");
        } else {
            $this->getView()->registerJs("
                var cache_{$id} = {};
                var cache_{$id}_1 = {};
                var cache_{$id}_2 = {};
                jQuery('#{$id}').autocomplete(
                {
                    minLength: 1,
                    source: function( request, response )
                    {
                        var term = request.term;
                        if ( term in cache_{$id} ) {
                            response( cache_{$id} [term] );
                            return;
                        }
                        $.getJSON('{$this->getUrl()}', request, function( data, status, xhr ) {
                            cache_{$id} [term] = data;
                            response(data);
                        });
                    },
                    select: function(event, ui)
                    {
                    
                    console.log(ui);
                    
                        afterSelect{$id}(event, ui);
                        
                        $('#{$id}-hidden').val(ui.item.id);
                         $('#{$id}-hidden').change();
                    }
                });
            ");
        }
        
        if ($value && $this->startQuery) {
            $this->getView()->registerJs("
                $(function(){
                    $.ajax({
                        type: 'GET',
                        dataType: 'json',
                        url: '{$this->getUrl()}',
                        data: {term: '$value'},
                        success: function(data) {

                            if (data.length == 0) {
                                $('#{$id}').attr('placeholder', 'User not found !!!');
                            } else {
                                var arr = [];
                                for (var i = 0; i<data.length; i++) {
                                    arr[i] = data[i].label;
                                    if (!(data[i].id in cache_{$id}_2)) {
                                        cache_{$id}_1[data[i].label] = data[i].id;
                                        cache_{$id}_2[data[i].id] = data[i].label;
                                    }
                                }
                                $('#{$id}').val(arr.join(', '));
                            }
                            $('.autocomplete-image-load').hide();
                        }
                    });
                });
            ");
        }
        
        return Html::tag('div', 
                
            Html::activeHiddenInput($this->model, $this->attribute, ['id' => $id . '-hidden', 'class' => 'form-control'])
            . ($value && $this->startQuery ? Html::tag('div', "<img src='{$this->registerActiveAssets()}/images/load.gif'/>", ['class' => 'autocomplete-image-load']) : '')
            . Html::textInput('', $value && !$this->startQuery ? $value : '', array_merge($this->options, ['id' => $id, 'class' => 'form-control']))
              
            , [
                'style' => 'position: relative;'
            ]
        );
    }
}
