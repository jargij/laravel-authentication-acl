<?php  namespace LaravelAcl\Authentication\Controllers;
/**
 * Class GroupController
 *
 * @author jacopo beschi jacopo@jacopobeschi.com
 */
use Illuminate\Support\MessageBag;
use LaravelAcl\Authentication\Presenters\GroupPresenter;
use LaravelAcl\Library\Form\FormModel;
use LaravelAcl\Authentication\Helpers\FormHelper;
use LaravelAcl\Authentication\Models\Group;
use LaravelAcl\Authentication\Exceptions\UserNotFoundException;
use LaravelAcl\Authentication\Validators\GroupValidator;
use LaravelAcl\Library\Exceptions\JacopoExceptionsInterface;
use View, Input, Redirect, App, Config;

class GroupController extends Controller
{
    /**
     * @var \LaravelAcl\Authentication\Repository\SentryGroupRepository
     */
    protected $group_repository;
    /**
     * @var \LaravelAcl\Authentication\Validators\GroupValidator
     */
    protected $group_validator;
    /**
     * @var FormHelper
     */
    protected $form_model;

    public function __construct(GroupValidator $v, FormHelper $fh)
    {
        $this->group_repository = App::make('group_repository');
        $this->group_validator = $v;
        $this->f = new FormModel($this->group_validator, $this->group_repository);
        $this->form_model = $fh;
    }

    public function getList()
    {
        $groups = $this->group_repository->all(Input::all());

        return View::make('laravel-authentication-acl::admin.group.list')->with(["groups" => $groups]);
    }

    public function editGroup()
    {
        try
        {
            $obj = $this->group_repository->find(Input::get('id'));
        }
        catch(UserNotFoundException $e)
        {
            $obj = new Group;
        }
        $presenter = new GroupPresenter($obj);

        return View::make('laravel-authentication-acl::admin.group.edit')->with(["group" => $obj, "presenter" => $presenter]);
    }

    public function postEditGroup()
    {
        $id = Input::get('id');

        try
        {
            $obj = $this->f->process(Input::all());
        }
        catch(JacopoExceptionsInterface $e)
        {
            $errors = $this->f->getErrors();
            // passing the id incase fails editing an already existing item
            return Redirect::route("groups.edit", $id ? ["id" => $id]: [])->withInput()->withErrors($errors);
        }
        return Redirect::route('groups.edit',["id" => $obj->id])->withMessage(Config::get('acl_messages.flash.success.group_edit_success'));
    }

    public function deleteGroup()
    {
        try
        {
            $this->f->delete(Input::all());
        }
        catch(JacopoExceptionsInterface $e)
        {
            $errors = $this->f->getErrors();
            return Redirect::route('groups.list')->withErrors($errors);
        }
        return Redirect::route('groups.list')->withMessage(Config::get('acl_messages.flash.success.group_delete_success'));
    }

    public function editPermission()
    {
        // prepare input
        $input = Input::all();
        $operation = Input::get('operation');
        $this->form_model->prepareSentryPermissionInput($input, $operation);
        $id = Input::get('id');

        try
        {
            $obj = $this->group_repository->update($id, $input);
        }
        catch(JacopoExceptionsInterface $e)
        {
            return Redirect::route("users.groups.edit")->withInput()->withErrors(new MessageBag(["permissions" => Config::get('acl_messages.flash.error.group_permission_not_found')]));
        }
        return Redirect::route('groups.edit',["id" => $obj->id])->withMessage(Config::get('acl_messages.flash.success.group_permission_edit_success'));
    }
}
