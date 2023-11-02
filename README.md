# Laravel db:seed with Rollback option

**Introduction**  
In the context of package development, particularly those involving database seeders, I propose the introduction of the   
`--rollback` option for the `db:seed` command. This feature complements the existing `migrate:rollback` command and offers a solution for efficient database seeding management.

**The Problem**  
Currently, many projects rely on packages that utilize seeders to alter the database. However, when complications arise, such as errors, changes in requirements, or the need to remove packages, there is a lack of streamlined mechanisms to revert these seeding changes. While the `migrate:rollback` command helps with migrations, the absence of a corresponding option for seeders presents challenges in complex projects where multiple custom packages are involved.

**The Solution**  
By introducing the `--rollback` option for the `db:seed` command, package developers and maintainers can now address these issues. 

**Use Case**  
Just run `db:seed` with the `--rollback` option and `down()` function will be invoked!
```bash
php artisan db:seed --class="Vendor\\Package\\Database\\\\Seeders\\\\UsersSeeder" --rollback 
#or
php artisan db:seed --rollback
```
```php
namespace Vendor\Package\Database\Seeders

/** If your seeder does not extend the package's Seeder:
 * Sonole\LaravelDbSeedRollback\Illuminate\Database\Seeder
 * then a new temporary file will be created at database/seeders, and it will be deleted after down() execution. 
*/
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         //\App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }

    /**
     * Reverse the effects of the database seeding operation.
     */
    public function down(): void
    {
        \App\Models\User::where('email', 'foo@bar.com')->delete();
    }
}
```
